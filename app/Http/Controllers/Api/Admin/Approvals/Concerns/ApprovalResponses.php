<?php

namespace App\Http\Controllers\Api\Admin\Approvals\Concerns;

use App\Support\SafeLog;
use Carbon\CarbonInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตัวช่วยร่วมของ endpoint คิวอนุมัติในแอปแอดมิน (/api/admin/approvals/*)
 *
 * - envelope เดียวกับ docs/ADMIN_APP_API.md: { success, data, message?, error_code?, errors? }
 * - ไม่คืนข้อความ exception ดิบ — log ผ่าน SafeLog แล้วตอบข้อความไทยกลาง ๆ
 * - delegateToWeb(): เรียกเมธอดของ controller หลังบ้านเว็บที่ตอบ JSON ได้อยู่แล้ว (RiderController / RiderJobController)
 *   → ได้การตรวจ/validation/ข้อความ/บริการชุดเดียวกับหน้าเว็บทุกตัว แล้วแปลง {code} เป็น {error_code}
 */
trait ApprovalResponses
{
    /**
     * JSON_PRESERVE_ZERO_FRACTION: เงิน/คะแนนเป็นทศนิยมเสมอบนสาย (140.0 ไม่ใช่ 140) — แอป Dart แยก int/double ตอน decode
     *
     * @param  mixed  $data
     */
    protected function ok($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $body = ['success' => true, 'data' => $data];
        if ($message !== null) {
            $body['message'] = $message;
        }

        return response()->json($body, $status, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @param  mixed  $data
     * @param  array<string, mixed>|null  $errors
     */
    protected function fail(string $message, string $code, int $status, $data = null, ?array $errors = null): JsonResponse
    {
        $body = ['success' => false, 'data' => $data, 'message' => $message, 'error_code' => $code];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function forbidden(string $message): JsonResponse
    {
        return $this->fail($message, 'PERMISSION_DENIED', 403);
    }

    protected function notFound(string $message): JsonResponse
    {
        return $this->fail($message, 'NOT_FOUND', 404);
    }

    /**
     * ข้อผิดพลาดที่ไม่คาดคิด → log (ปิดความลับแล้ว) + ข้อความไทยกลาง ๆ
     *
     * @param  array<string, mixed>  $context
     */
    protected function serverError(string $action, \Throwable $e, array $context = []): JsonResponse
    {
        Log::error('AdminApp approvals: '.$action.' failed', $context + [
            'admin_id' => auth()->id(),
            'error' => SafeLog::exceptionMessage($e),
            'exception' => class_basename($e),
        ]);

        return $this->fail('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'SERVER_ERROR', 500);
    }

    /**
     * ตรวจข้อมูลเข้า — ไม่ผ่าน = 422 envelope + errors ของ Laravel + message = ข้อผิดพลาดแรก (ภาษาไทย)
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, mixed>
     */
    protected function validateInput(Request $request, array $rules, array $messages = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new HttpResponseException($this->fail(
                (string) $validator->errors()->first(),
                'VALIDATION_ERROR',
                422,
                null,
                $validator->errors()->toArray()
            ));
        }

        return $validator->validated();
    }

    /**
     * paging แบบแบนของแอป: { data: { data: [...], current_page, last_page, per_page, total } }
     *
     * @param  array<int, mixed>  $items
     * @param  array<string, mixed>  $extra  ฟิลด์เพิ่มข้าง paging (เช่น summary)
     */
    protected function paged(LengthAwarePaginator $page, array $items, array $extra = []): JsonResponse
    {
        return $this->ok([
            'data' => $items,
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ] + $extra);
    }

    /**
     * กฎ per_page/page มาตรฐาน (1–100 ค่าเริ่มต้น 20)
     *
     * @return array<string, string>
     */
    protected function pagingRules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    protected function iso(?CarbonInterface $at): ?string
    {
        return $at?->toIso8601String();
    }

    protected function minutesSince(?CarbonInterface $at): ?int
    {
        if ($at === null) {
            return null;
        }

        return max(0, (int) floor((now()->getTimestamp() - $at->getTimestamp()) / 60));
    }

    /**
     * ชื่อแบบย่อ (ไม่ส่งอีเมล/เบอร์ในรายการ — PDPA)
     */
    protected function personRef(?\App\Models\User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => (int) $user->id,
            'name' => (string) ($user->name ?? ''),
            'member_number' => $user->member_number ?? null,
        ];
    }

    /**
     * เบอร์โทรแบบปิดบัง เช่น 0812345678 → 081•••5678
     */
    protected function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) <= 6) {
            return str_repeat('•', strlen($digits));
        }

        return substr($digits, 0, 3).str_repeat('•', strlen($digits) - 7).substr($digits, -4);
    }

    /**
     * เรียกเมธอดของ controller หลังบ้านเว็บที่ตอบ JSON ได้ (respond() ดู expectsJson) แล้วแปลงเป็น envelope ของแอป
     *
     * @param  callable(): Response  $call
     */
    protected function delegateToWeb(Request $request, callable $call, string $action): JsonResponse
    {
        // บังคับทางตอบ JSON ของ controller เว็บ (แอปบางตัวไม่ส่ง Accept มา)
        $request->headers->set('Accept', 'application/json');

        try {
            $response = $call();
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->fail('ไม่พบรายการที่ต้องการ', 'NOT_FOUND', 404);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            $status = $e->getStatusCode();

            return $this->fail(
                $status === 404 ? 'ไม่พบรายการที่ต้องการ' : 'ทำรายการไม่สำเร็จ',
                $status === 404 ? 'NOT_FOUND' : 'HTTP_'.$status,
                $status
            );
        } catch (\Throwable $e) {
            return $this->serverError($action, $e);
        }

        if (! $response instanceof JsonResponse) {
            Log::warning('AdminApp approvals: web action did not return JSON', ['action' => $action]);

            return $this->fail('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'SERVER_ERROR', 500);
        }

        $body = (array) $response->getData(true);
        $success = (bool) ($body['success'] ?? false);
        $status = $response->getStatusCode();
        $message = (string) ($body['message'] ?? '');
        $data = $body['data'] ?? null;

        if ($success) {
            return $this->ok($data, $message !== '' ? $message : null, $status);
        }

        return $this->fail(
            $message !== '' ? $message : 'ทำรายการไม่สำเร็จ',
            (string) ($body['code'] ?? 'ERROR'),
            $status >= 400 ? $status : 422,
            $data
        );
    }
}
