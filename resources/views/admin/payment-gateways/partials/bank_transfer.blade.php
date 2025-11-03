<!-- Bank Transfer Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fas fa-university mr-2"></i>
            การตั้งค่าโอนผ่านธนาคาร
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-green-50 rounded-xl border border-green-200 mb-4">
            <p class="text-sm text-green-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> กำหนดบัญชีธนาคารที่ใช้รับเงินจากลูกค้า ลูกค้าจะโอนเงินและอัพโหลดสลิปเพื่อยืนยัน
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-university text-green-500 mr-1"></i> ชื่อธนาคาร <span class="text-red-500">*</span>
            </label>
            <select name="credentials[bank_name]"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    required>
                <option value="">เลือกธนาคาร</option>
                <option value="ธนาคารกรุงเทพ" {{ old('credentials.bank_name', $paymentGateway->getCredential('bank_name')) === 'ธนาคารกรุงเทพ' ? 'selected' : '' }}>ธนาคารกรุงเทพ (BBL)</option>
                <option value="ธนาคารกสิกรไทย" {{ old('credentials.bank_name', $paymentGateway->getCredential('bank_name')) === 'ธนาคารกสิกรไทย' ? 'selected' : '' }}>ธนาคารกสิกรไทย (KBANK)</option>
                <option value="ธนาคารกรุงไทย" {{ old('credentials.bank_name', $paymentGateway->getCredential('bank_name')) === 'ธนาคารกรุงไทย' ? 'selected' : '' }}>ธนาคารกรุงไทย (KTB)</option>
                <option value="ธนาคารทหารไทยธนชาต" {{ old('credentials.bank_name', $paymentGateway->getCredential('bank_name')) === 'ธนาคารทหารไทยธนชาต' ? 'selected' : '' }}>ธนาคารทหารไทยธนชาต (TTB)</option>
                <option value="ธนาคารไทยพาณิชย์" {{ old('credentials.bank_name', $paymentGateway->getCredential('bank_name')) === 'ธนาคารไทยพาณิชย์' ? 'selected' : '' }}>ธนาคารไทยพาณิชย์ (SCB)</option>
                <option value="ธนาคารกรุงศรีอยุธยา" {{ old('credentials.bank_name', $paymentGateway->getCredential('bank_name')) === 'ธนาคารกรุงศรีอยุธยา' ? 'selected' : '' }}>ธนาคารกรุงศรีอยุธยา (BAY)</option>
                <option value="ธนาคารเกียรตินาคินภัทร" {{ old('credentials.bank_name', $paymentGateway->getCredential('bank_name')) === 'ธนาคารเกียรตินาคินภัทร' ? 'selected' : '' }}>ธนาคารเกียรตินาคินภัทร (KKP)</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-hashtag text-green-500 mr-1"></i> เลขที่บัญชี <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[account_number]"
                   value="{{ old('credentials.account_number', $paymentGateway->getCredential('account_number')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                   placeholder="123-4-56789-0"
                   required>
            <p class="text-xs text-gray-500 mt-1">ตัวอย่าง: 123-4-56789-0</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-user text-green-500 mr-1"></i> ชื่อบัญชี <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[account_name]"
                   value="{{ old('credentials.account_name', $paymentGateway->getCredential('account_name')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                   placeholder="นายสมชาย ใจดี"
                   required>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-building text-green-500 mr-1"></i> สาขา
            </label>
            <input type="text" name="credentials[branch]"
                   value="{{ old('credentials.branch', $paymentGateway->getCredential('branch')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                   placeholder="สาขาสยามสแควร์">
        </div>

        <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-green-500 text-xl mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-semibold text-green-900 mb-2">วิธีการใช้งาน</p>
                    <ol class="text-green-700 space-y-1 list-decimal list-inside">
                        <li>กรอกข้อมูลบัญชีธนาคารของคุณ</li>
                        <li>ลูกค้าจะเห็นข้อมูลบัญชีและโอนเงิน</li>
                        <li>ลูกค้าอัพโหลดสลิปการโอนเงิน</li>
                        <li>แอดมินตรวจสอบและอนุมัติการฝากเงิน</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
