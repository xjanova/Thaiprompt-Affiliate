<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process as SymfonyProcess;

class DeploymentController extends Controller
{
    /**
     * Execute git command safely
     */
    private function execGit($command, $default = 'unknown')
    {
        try {
            $process = new SymfonyProcess(
                array_merge(['git'], $command),
                base_path(),
                null,
                null,
                10 // 10 seconds timeout
            );

            $process->run();

            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }

            return $default;
        } catch (\Exception $e) {
            Log::warning('Git command failed: ' . implode(' ', $command), ['error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * Show deployment center
     */
    public function index()
    {
        // Get current git info
        $currentBranch = $this->execGit(['rev-parse', '--abbrev-ref', 'HEAD'], 'unknown');
        $currentCommit = $this->execGit(['rev-parse', 'HEAD'], 'unknown');
        $currentCommitShort = substr($currentCommit, 0, 8);
        $currentCommitMessage = $this->execGit(['log', '-1', '--pretty=%B'], '');

        // Get recent deployments
        $recentDeployments = Deployment::with('startedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Check if deployment is currently running
        $isDeploying = Cache::has('deployment_lock');
        $runningDeployment = null;
        if ($isDeploying) {
            $runningDeployment = Deployment::where('status', Deployment::STATUS_RUNNING)
                ->latest()
                ->first();
        }

        // Get last successful deployment
        $lastDeployment = Deployment::where('status', Deployment::STATUS_COMPLETED)
            ->latest()
            ->first();

        return view('admin.deployment.index', compact(
            'currentBranch',
            'currentCommit',
            'currentCommitShort',
            'currentCommitMessage',
            'recentDeployments',
            'isDeploying',
            'runningDeployment',
            'lastDeployment'
        ));
    }

    /**
     * Check for updates from GitHub
     */
    public function checkUpdates(Request $request)
    {
        try {
            $branch = $request->get('branch', $this->execGit(['rev-parse', '--abbrev-ref', 'HEAD'], 'main'));

            // Fetch latest from remote
            $this->execGit(['fetch', 'origin', $branch]);

            // Get local and remote commits
            $localCommit = $this->execGit(['rev-parse', 'HEAD'], '');
            $remoteCommit = $this->execGit(['rev-parse', 'origin/' . $branch], '');

            $hasUpdates = $localCommit !== $remoteCommit && !empty($localCommit) && !empty($remoteCommit);

            // Get commit log if there are updates
            $commits = [];
            if ($hasUpdates) {
                $commitLog = $this->execGit(['log', '--oneline', 'HEAD..origin/' . $branch], '');
                if ($commitLog) {
                    $lines = explode("\n", trim($commitLog));
                    foreach ($lines as $line) {
                        if (preg_match('/^([a-f0-9]+)\s+(.+)$/', $line, $matches)) {
                            $commits[] = [
                                'hash' => $matches[1],
                                'message' => $matches[2],
                            ];
                        }
                    }
                }
            }

            return response()->json([
                'has_updates' => $hasUpdates,
                'local_commit' => substr($localCommit, 0, 8),
                'remote_commit' => substr($remoteCommit, 0, 8),
                'commits_count' => count($commits),
                'commits' => $commits,
                'branch' => $branch,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start deployment
     */
    public function start(Request $request)
    {
        // Check if already deploying
        if (Cache::has('deployment_lock')) {
            return response()->json([
                'error' => 'A deployment is already in progress. Please wait for it to complete.'
            ], 422);
        }

        // Get branch
        $branch = $request->get('branch', $this->execGit(['rev-parse', '--abbrev-ref', 'HEAD'], 'main'));

        // Get current commit for rollback
        $currentCommit = $this->execGit(['rev-parse', 'HEAD'], '');

        // Create deployment record
        $deployment = Deployment::create([
            'status' => Deployment::STATUS_PENDING,
            'branch' => $branch,
            'started_by' => auth()->id(),
            'rollback_commit' => $currentCommit,
        ]);

        // Set lock (30 minutes)
        Cache::put('deployment_lock', $deployment->id, now()->addMinutes(30));

        return response()->json([
            'success' => true,
            'deployment_id' => $deployment->id,
            'message' => 'Deployment started'
        ]);
    }

    /**
     * Stream deployment logs (SSE - Server-Sent Events)
     */
    public function stream($id)
    {
        $deployment = Deployment::findOrFail($id);

        // Check if deployment is already completed
        if ($deployment->isCompleted() || $deployment->isFailed()) {
            return response($deployment->output ?: 'Deployment already completed.')
                ->header('Content-Type', 'text/plain');
        }

        return response()->stream(function () use ($deployment) {
            // Mark as running
            $deployment->markAsRunning();

            // Set output buffer
            ob_implicit_flush(true);
            ob_end_flush();

            $startTime = time();
            $output = '';

            try {
                // Get project root
                $projectRoot = base_path();

                // Check if deploy.sh exists
                $deployScript = $projectRoot . '/deploy.sh';
                if (!file_exists($deployScript)) {
                    throw new \Exception('deploy.sh not found at: ' . $deployScript);
                }

                // Make sure it's executable
                chmod($deployScript, 0755);

                // Run deploy.sh
                $process = new SymfonyProcess(
                    ['bash', $deployScript, $deployment->branch],
                    $projectRoot,
                    null,
                    null,
                    1800 // 30 minutes timeout
                );

                $process->start();

                // Stream output
                foreach ($process as $type => $data) {
                    $output .= $data;

                    // Send to browser
                    echo "data: " . json_encode([
                        'type' => 'log',
                        'message' => $data
                    ]) . "\n\n";

                    flush();
                }

                // Wait for process to finish
                $exitCode = $process->wait();

                // Get final commit info
                $newCommit = $this->execGit(['rev-parse', 'HEAD'], '');
                $newCommitMessage = $this->execGit(['log', '-1', '--pretty=%B'], '');

                $duration = time() - $startTime;

                if ($exitCode === 0) {
                    // Success
                    $deployment->update([
                        'commit_hash' => $newCommit,
                        'commit_message' => $newCommitMessage,
                        'rollback_available' => true,
                    ]);
                    $deployment->markAsCompleted($output, $duration);

                    echo "data: " . json_encode([
                        'type' => 'complete',
                        'success' => true,
                        'message' => 'Deployment completed successfully!',
                        'duration' => $duration
                    ]) . "\n\n";
                } else {
                    // Failed
                    $deployment->markAsFailed('Deployment script failed with exit code: ' . $exitCode, $output);

                    echo "data: " . json_encode([
                        'type' => 'complete',
                        'success' => false,
                        'message' => 'Deployment failed!',
                        'error' => 'Exit code: ' . $exitCode
                    ]) . "\n\n";
                }

            } catch (\Exception $e) {
                $duration = time() - $startTime;
                $error = $e->getMessage();

                $deployment->markAsFailed($error, $output);

                echo "data: " . json_encode([
                    'type' => 'complete',
                    'success' => false,
                    'message' => 'Deployment failed!',
                    'error' => $error
                ]) . "\n\n";

                Log::error('Deployment failed', [
                    'deployment_id' => $deployment->id,
                    'error' => $error,
                ]);
            } finally {
                // Release lock
                Cache::forget('deployment_lock');
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    /**
     * Get deployment status
     */
    public function status($id)
    {
        $deployment = Deployment::with('startedBy')->findOrFail($id);

        return response()->json([
            'deployment' => [
                'id' => $deployment->id,
                'status' => $deployment->status,
                'branch' => $deployment->branch,
                'commit_hash' => $deployment->short_commit,
                'commit_message' => $deployment->commit_message,
                'started_by' => $deployment->startedBy?->name,
                'started_at' => $deployment->started_at?->format('Y-m-d H:i:s'),
                'completed_at' => $deployment->completed_at?->format('Y-m-d H:i:s'),
                'duration' => $deployment->duration_human,
                'output' => $deployment->output,
                'error' => $deployment->error,
            ]
        ]);
    }

    /**
     * Get deployment history
     */
    public function history(Request $request)
    {
        $perPage = $request->get('per_page', 20);

        $deployments = Deployment::with('startedBy')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($deployments);
    }

    /**
     * Rollback to previous deployment
     */
    public function rollback(Request $request, $id)
    {
        $deployment = Deployment::findOrFail($id);

        if (!$deployment->rollback_available || !$deployment->rollback_commit) {
            return response()->json([
                'error' => 'Rollback not available for this deployment'
            ], 422);
        }

        // Check if already deploying
        if (Cache::has('deployment_lock')) {
            return response()->json([
                'error' => 'A deployment is already in progress'
            ], 422);
        }

        // Create rollback deployment
        $rollbackDeployment = Deployment::create([
            'status' => Deployment::STATUS_PENDING,
            'branch' => $deployment->branch,
            'commit_hash' => $deployment->rollback_commit,
            'commit_message' => 'Rollback to: ' . $deployment->rollback_commit,
            'started_by' => auth()->id(),
        ]);

        // Set lock
        Cache::put('deployment_lock', $rollbackDeployment->id, now()->addMinutes(30));

        return response()->json([
            'success' => true,
            'deployment_id' => $rollbackDeployment->id,
            'message' => 'Rollback started'
        ]);
    }

    /**
     * Cancel deployment
     */
    public function cancel($id)
    {
        $deployment = Deployment::findOrFail($id);

        if (!$deployment->isRunning()) {
            return response()->json([
                'error' => 'Cannot cancel deployment in current status'
            ], 422);
        }

        $deployment->update(['status' => Deployment::STATUS_CANCELLED]);
        Cache::forget('deployment_lock');

        return response()->json([
            'success' => true,
            'message' => 'Deployment cancelled'
        ]);
    }
}
