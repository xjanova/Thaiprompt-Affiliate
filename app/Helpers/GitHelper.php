<?php

namespace App\Helpers;

/**
 * GitHelper - Read git information directly from .git directory
 * No shell execution required - works even with all exec functions disabled
 */
class GitHelper
{
    private static $gitDir;

    /**
     * Initialize git directory path
     */
    private static function getGitDir()
    {
        if (self::$gitDir) {
            return self::$gitDir;
        }

        $gitDir = base_path('.git');
        if (!is_dir($gitDir)) {
            return null;
        }

        self::$gitDir = $gitDir;
        return $gitDir;
    }

    /**
     * Get current branch name
     */
    public static function getCurrentBranch()
    {
        $gitDir = self::getGitDir();
        if (!$gitDir) {
            return 'unknown';
        }

        $headFile = $gitDir . '/HEAD';
        if (!file_exists($headFile)) {
            return 'unknown';
        }

        $head = trim(file_get_contents($headFile));

        // HEAD file contains: ref: refs/heads/main
        if (preg_match('#^ref: refs/heads/(.+)$#', $head, $matches)) {
            return $matches[1];
        }

        // Detached HEAD state
        return 'detached';
    }

    /**
     * Get commit hash for a branch
     */
    public static function getCommitHash($branch = null)
    {
        $gitDir = self::getGitDir();
        if (!$gitDir) {
            return 'unknown';
        }

        if ($branch === null) {
            $branch = self::getCurrentBranch();
        }

        if ($branch === 'unknown' || $branch === 'detached') {
            // Try to get hash from HEAD directly
            $headFile = $gitDir . '/HEAD';
            if (file_exists($headFile)) {
                $head = trim(file_get_contents($headFile));
                if (preg_match('/^[a-f0-9]{40}$/', $head)) {
                    return $head;
                }
            }
            return 'unknown';
        }

        // Try to read from refs/heads/{branch}
        $branchFile = $gitDir . '/refs/heads/' . $branch;
        if (file_exists($branchFile)) {
            return trim(file_get_contents($branchFile));
        }

        // Try packed-refs
        $packedRefs = $gitDir . '/packed-refs';
        if (file_exists($packedRefs)) {
            $content = file_get_contents($packedRefs);
            $pattern = '/^([a-f0-9]{40})\s+refs\/heads\/' . preg_quote($branch, '/') . '$/m';
            if (preg_match($pattern, $content, $matches)) {
                return $matches[1];
            }
        }

        return 'unknown';
    }

    /**
     * Get commit hash for a remote branch
     */
    public static function getRemoteCommitHash($branch, $remote = 'origin')
    {
        $gitDir = self::getGitDir();
        if (!$gitDir) {
            return '';
        }

        // Try to read from refs/remotes/{remote}/{branch}
        $remoteBranchFile = $gitDir . '/refs/remotes/' . $remote . '/' . $branch;
        if (file_exists($remoteBranchFile)) {
            return trim(file_get_contents($remoteBranchFile));
        }

        // Try packed-refs
        $packedRefs = $gitDir . '/packed-refs';
        if (file_exists($packedRefs)) {
            $content = file_get_contents($packedRefs);
            $pattern = '/^([a-f0-9]{40})\s+refs\/remotes\/' . preg_quote($remote, '/') . '\/' . preg_quote($branch, '/') . '$/m';
            if (preg_match($pattern, $content, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }

    /**
     * Get commit message from commit hash
     */
    public static function getCommitMessage($commitHash)
    {
        $gitDir = self::getGitDir();
        if (!$gitDir || $commitHash === 'unknown' || strlen($commitHash) < 40) {
            return '';
        }

        // Git objects are stored as .git/objects/ab/cdef123...
        $objectPath = $gitDir . '/objects/' . substr($commitHash, 0, 2) . '/' . substr($commitHash, 2);

        if (!file_exists($objectPath)) {
            // Try to read from pack files (more complex, we'll skip for now)
            return '';
        }

        // Read and decompress the object
        $compressed = file_get_contents($objectPath);
        $decompressed = @gzuncompress($compressed);

        if ($decompressed === false) {
            return '';
        }

        // Parse git object format
        // Format: "commit {size}\0{content}"
        // Content includes: tree, parent, author, committer, then commit message

        $nullPos = strpos($decompressed, "\0");
        if ($nullPos === false) {
            return '';
        }

        $content = substr($decompressed, $nullPos + 1);

        // Split by double newline to get message
        $parts = explode("\n\n", $content, 2);
        if (count($parts) < 2) {
            return '';
        }

        return trim($parts[1]);
    }

    /**
     * Get commits between two refs
     */
    public static function getCommitsBetween($from, $to)
    {
        // This is complex to implement without shell exec
        // For now, return empty array
        // Would need to traverse the commit graph recursively
        return [];
    }

    /**
     * Check if git is available
     */
    public static function isAvailable()
    {
        return self::getGitDir() !== null;
    }
}
