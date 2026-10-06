<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * 🧩 ApprovalResponses::delegateToWeb — exception ของเมธอดหลังบ้านเว็บต้องกลายเป็น envelope ของแอป (ไม่ใช่ 500)
 *
 * ไม่แตะฐานข้อมูล — เรียก trait ตรงผ่านคลาสจำลอง
 */
#[Group('admin-app')]
class ApprovalResponsesDelegateTest extends TestCase
{
    private function host(): object
    {
        return new class
        {
            use ApprovalResponses;

            public function run(Request $request, callable $call): JsonResponse
            {
                return $this->delegateToWeb($request, $call, 'test_action');
            }
        };
    }

    public function test_validation_exception_becomes_422_envelope_with_thai_message(): void
    {
        $res = $this->host()->run(Request::create('/x', 'POST'), function () {
            throw ValidationException::withMessages(['reason' => ['กรุณาระบุเหตุผลที่คืนเงิน']]);
        });

        $this->assertSame(422, $res->getStatusCode());
        $body = $res->getData(true);
        $this->assertFalse($body['success']);
        $this->assertSame('VALIDATION_ERROR', $body['error_code']);
        $this->assertSame('กรุณาระบุเหตุผลที่คืนเงิน', $body['message']);
        $this->assertSame(['reason' => ['กรุณาระบุเหตุผลที่คืนเงิน']], $body['errors']);
    }

    public function test_authorization_exception_becomes_403_permission_denied(): void
    {
        $res = $this->host()->run(Request::create('/x', 'POST'), function () {
            throw new AuthorizationException('This action is unauthorized.');
        });

        $this->assertSame(403, $res->getStatusCode());
        $body = $res->getData(true);
        $this->assertSame('PERMISSION_DENIED', $body['error_code']);
        $this->assertSame('ไม่มีสิทธิ์ทำรายการนี้', $body['message']);
        $this->assertStringNotContainsString('unauthorized', $res->getContent(), 'ไม่คืนข้อความ exception ดิบ');
    }

    public function test_web_json_failure_maps_code_to_error_code(): void
    {
        $res = $this->host()->run(Request::create('/x', 'POST'), fn () => response()->json([
            'success' => false,
            'message' => 'การส่งมอบนี้ปิดไปแล้ว',
            'code' => 'HANDOVER_FINAL',
            'data' => null,
        ], 409));

        $this->assertSame(409, $res->getStatusCode());
        $this->assertSame('HANDOVER_FINAL', $res->getData(true)['error_code']);
    }
}
