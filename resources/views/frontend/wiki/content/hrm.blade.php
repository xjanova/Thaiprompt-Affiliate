{{-- Hero Section --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 3rem 2rem; border-radius: 20px; margin-bottom: 3rem; color: white; text-align: center;">
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">👥 Human Resource Management (HRM)</h1>
    <p style="font-size: 1.25rem; opacity: 0.95; max-width: 800px; margin: 0 auto;">ระบบบริหารทรัพยากรบุคคลครบวงจร จัดการพนักงาน ลา เงินเดือน และประเมินผลอัตโนมัติ</p>
</section>

{{-- 1: Employee Management --}}
<section id="employee" class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">👥 Employee Management System</h2>

    <div class="info-box success">
        <h4>🎯 ระบบจัดการพนักงานแบบครบวงจร</h4>
        <p>จัดการข้อมูลพนักงานทุกอย่างในที่เดียว ตั้งแต่เอกสารส่วนตัว สัญญาจ้าง จนถึงโครงสร้างองค์กร</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">📋 Employee Profile & Data</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">👤</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Personal Information</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">ข้อมูลส่วนบุคคล</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ ข้อมูลพื้นฐาน (ชื่อ, เลขบัตร, ที่อยู่)</li>
                <li>✅ Contact Info (Email, Phone)</li>
                <li>✅ Emergency Contact</li>
                <li>✅ Family Information</li>
                <li>✅ Profile Picture & ID Card</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Employment Details</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">ข้อมูลการจ้างงาน</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Employee ID & Join Date</li>
                <li>✅ Department & Position</li>
                <li>✅ Employment Type (Full-time/Part-time)</li>
                <li>✅ Job Description</li>
                <li>✅ Reporting Manager</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📑</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Documents Management</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">จัดการเอกสาร</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Upload & Store Documents</li>
                <li>✅ Contract & Agreements</li>
                <li>✅ Certificates & Licenses</li>
                <li>✅ Resignation Letters</li>
                <li>✅ Auto Expiry Reminder</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🏢</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Organization Chart</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">โครงสร้างองค์กร</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Visual Hierarchy Tree</li>
                <li>✅ Department Structure</li>
                <li>✅ Reporting Lines</li>
                <li>✅ Drag & Drop Interface</li>
                <li>✅ Export to PDF/Image</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔐</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Access & Permissions</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">สิทธิ์การเข้าถึง</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Role-based Access Control</li>
                <li>✅ Data Privacy Settings</li>
                <li>✅ Audit Log Tracking</li>
                <li>✅ Multi-level Approval</li>
                <li>✅ PDPA Compliance</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Employee Dashboard</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">แดชบอร์ดพนักงาน</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Self-service Portal</li>
                <li>✅ View Payslips</li>
                <li>✅ Request Leave/OT</li>
                <li>✅ Update Personal Info</li>
                <li>✅ Announcements & News</li>
            </ul>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Employee Management Best Practices</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Digital Onboarding:</strong> ใช้ระบบ Onboarding ดิจิทัล ให้พนักงานใหม่กรอกข้อมูลและอัปโหลดเอกสารเอง</li>
            <li><strong>Document Expiry:</strong> ตั้งค่าแจ้งเตือนก่อนเอกสารหมดอายุ เช่น ใบขับขี่ ใบอนุญาตทำงาน</li>
            <li><strong>Data Backup:</strong> สำรองข้อมูลพนักงานอัตโนมัติทุกวัน เก็บไว้อย่างปลอดภัย</li>
            <li><strong>PDPA Compliance:</strong> ขออนุญาตเก็บข้อมูลส่วนบุคคล และให้สิทธิ์พนักงานขอลบข้อมูลได้</li>
        </ul>
    </div>
</section>

{{-- 2: Attendance & Leave --}}
<section id="attendance" class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">⏰ Attendance & Leave Management</h2>

    <div class="info-box success">
        <h4>📍 ระบบลงเวลาและการลา</h4>
        <p>บันทึกเวลาเข้า-ออกงาน จัดการวันลา OT และกะการทำงาน แบบอัตโนมัติและแม่นยำ</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">⏱️ Time Attendance Methods</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">👆</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Fingerprint Scanner</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Biometric Fingerprint</li>
                <li>✅ Fast & Accurate</li>
                <li>✅ No Buddy Punching</li>
                <li>✅ Connect via LAN/WiFi</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">😊</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Face Recognition</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ AI Face Detection</li>
                <li>✅ Touchless & Hygienic</li>
                <li>✅ Temperature Check</li>
                <li>✅ Mask Detection</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">📱</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Mobile Check-in</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ GPS Location Tracking</li>
                <li>✅ Geofencing Area</li>
                <li>✅ Photo Capture</li>
                <li>✅ Work from Home Support</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">💻</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Web Clock-in</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Browser-based</li>
                <li>✅ IP Restriction</li>
                <li>✅ Screenshot Capture</li>
                <li>✅ One-click Check</li>
            </ul>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🏖️ Leave Management</h3>

    <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
        <thead style="background: var(--wiki-card-bg);">
            <tr>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Leave Type</th>
                <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Annual Quota</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🏖️ Annual Leave</strong></td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">6-20 วัน</td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ลาพักผ่อน ตามอายุงาน</td>
            </tr>
            <tr style="background: var(--wiki-card-bg);">
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🤒 Sick Leave</strong></td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">30 วัน</td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ลาป่วย แนบใบรับรองแพทย์</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💼 Business Leave</strong></td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">3 วัน</td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ลากิจส่วนตัว</td>
            </tr>
            <tr style="background: var(--wiki-card-bg);">
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>👶 Maternity Leave</strong></td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">98 วัน</td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ลาคลอดบุตร (ตามกฎหมาย)</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⚰️ Funeral Leave</strong></td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1-3 วัน</td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ลาเพื่อจัดการศพ</td>
            </tr>
            <tr style="background: var(--wiki-card-bg);">
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💍 Wedding Leave</strong></td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">3 วัน</td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ลาแต่งงาน</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🏠 Work from Home</strong></td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Flexible</td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ทำงานจากบ้าน (ตามนโยบาย)</td>
            </tr>
        </tbody>
    </table>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">⏰ Shift & OT Management</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🔄 Shift Scheduling</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">กะเช้า, กะบ่าย, กะดึก พร้อม Rotation</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">⏱️ OT Calculator</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">คำนวณ OT 1.5x, 3x อัตโนมัติตามกฎหมาย</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📊 Timesheet Reports</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">รายงานเวลาทำงาน สาย ขาด ลา</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">⚠️ Late & Absence Alert</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">แจ้งเตือนมาสาย ขาดงาน อัตโนมัติ</p>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Attendance Best Practices</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Grace Period:</strong> ให้เวลา Grace Period 5-10 นาที สำหรับการมาสาย</li>
            <li><strong>Mobile Check-in:</strong> สำหรับพนักงานขาย/ภาคสนาม ใช้ Mobile + GPS</li>
            <li><strong>Flexible Time:</strong> พิจารณาระบบ Flextime สำหรับบางตำแหน่ง</li>
            <li><strong>Overtime Approval:</strong> กำหนดให้ต้องขออนุมัติ OT ก่อนทำ ป้องกันปัญหา</li>
        </ul>
    </div>
</section>

{{-- 3: Payroll System --}}
<section id="payroll" class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">💵 Payroll Management System</h2>

    <div class="info-box success">
        <h4>💰 ระบบคำนวณเงินเดือนอัตโนมัติ</h4>
        <p>คำนวณเงินเดือน โบนัส ภาษี ประกันสังคม และสร้างสลิปเงินเดือนอัตโนมัติ แม่นยำทุกรายการ</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">💵 Salary Components</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem; color: var(--wiki-success);">➕</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-success);">Income (รายได้)</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Base Salary (เงินเดือนพื้นฐาน)</li>
                <li>✅ Overtime (OT 1.5x, 3x)</li>
                <li>✅ Commission (ค่าคอมมิชชั่น)</li>
                <li>✅ Allowances (ค่าเดินทาง, ที่พัก)</li>
                <li>✅ Bonus (โบนัสประจำปี)</li>
                <li>✅ Incentives (ค่ารางวัล)</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='var(--wiki-danger)'; this.style.boxShadow='0 8px 24px rgba(239, 68, 68, 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem; color: var(--wiki-danger);">➖</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-danger);">Deductions (รายการหัก)</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>❌ Tax Withholding (ภาษีหัก ณ ที่จ่าย)</li>
                <li>❌ Social Security (ประกันสังคม 5%)</li>
                <li>❌ Provident Fund (กองทุนสำรอง)</li>
                <li>❌ Late/Absent Deduction</li>
                <li>❌ Loan Repayment (หักชำระหนี้)</li>
                <li>❌ Advance Salary Deduction</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Net Salary</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">เงินเดือนสุทธิ</p>
            <div style="background: var(--wiki-hover-bg); padding: 1.5rem; border-radius: 8px; margin-top: 1rem; font-family: monospace; font-size: 0.9rem; line-height: 2;">
                <strong>Gross Salary:</strong> 30,000 บาท<br>
                <span style="color: var(--wiki-success);">+ OT:</span> 3,000 บาท<br>
                <span style="color: var(--wiki-success);">+ Allowance:</span> 2,000 บาท<br>
                <span style="color: var(--wiki-danger);">- Tax:</span> -2,500 บาท<br>
                <span style="color: var(--wiki-danger);">- SSO:</span> -750 บาท<br>
                <hr style="margin: 0.5rem 0; border-color: var(--wiki-border);">
                <strong style="font-size: 1.1rem; color: rgb(var(--primary-rgb));">Net Pay: 31,750 บาท</strong>
            </div>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">📄 Payslip & Tax</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📧 Digital Payslip</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ส่งสลิปเงินเดือนทาง Email อัตโนมัติ</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🔒 Secure PDF</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">PDF มีรหัสผ่าน ปลอดภัย PDPA</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">💰 Tax Calculation</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">คำนวณภาษี progressive tax อัตโนมัติ</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📋 ภงด.1, ภงด.91</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">สร้างเอกสารภาษีให้พนักงานอัตโนมัติ</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🏦 Bank Integration</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">โอนเงินเดือนผ่านไฟล์ BAHTNET</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📊 Payroll Reports</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">รายงาน Payroll Summary, Cost Center</p>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Payroll Best Practices</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Payroll Calendar:</strong> กำหนดวันจ่ายเงินเดือนชัดเจน (เช่น วันที่ 25 ของทุกเดือน)</li>
            <li><strong>Double Check:</strong> ตรวจสอบข้อมูลก่อนจ่ายทุกครั้ง Run Payroll 2-3 วันก่อน</li>
            <li><strong>Backup Data:</strong> สำรองข้อมูล Payroll ทุกเดือน เก็บไว้อย่างน้อย 7 ปี</li>
            <li><strong>Tax Compliance:</strong> ยื่นภาษี SSO ตรงเวลา หลีกเลี่ยงค่าปรับ</li>
            <li><strong>Confidentiality:</strong> ข้อมูลเงินเดือนเป็นความลับ จำกัดสิทธิ์การเข้าถึง</li>
        </ul>
    </div>
</section>

{{-- 4: Performance & KPI --}}
<section id="performance" class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">📊 Performance & KPI Management</h2>

    <div class="info-box success">
        <h4>🎯 ระบบประเมินผลและ KPI</h4>
        <p>ติดตาม KPI ประเมินผลพนักงาน และวางแผนพัฒนาอาชีพ ด้วยระบบที่ชัดเจนและโปร่งใส</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🎯 KPI & Goal Setting</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎯</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">SMART Goals</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">เป้าหมายที่วัดผลได้</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ <strong>S</strong>pecific - เฉพาะเจาะจง</li>
                <li>✅ <strong>M</strong>easurable - วัดผลได้</li>
                <li>✅ <strong>A</strong>chievable - ทำได้จริง</li>
                <li>✅ <strong>R</strong>elevant - เกี่ยวข้องกับงาน</li>
                <li>✅ <strong>T</strong>ime-bound - มีกำหนดเวลา</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📈</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">KPI Tracking</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">ติดตามผลงาน</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Real-time Dashboard</li>
                <li>✅ Progress Bar & Charts</li>
                <li>✅ Auto Notification</li>
                <li>✅ Department KPI</li>
                <li>✅ Individual KPI</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔄</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">OKR System</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">Objectives & Key Results</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Company Objectives</li>
                <li>✅ Team Objectives</li>
                <li>✅ Individual Alignment</li>
                <li>✅ Quarterly Review</li>
                <li>✅ Transparency</li>
            </ul>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">⭐ Performance Review Process</h3>

    <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;">
            <div style="text-align: center; flex: 1; min-width: 150px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--primary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">1</div>
                <div style="font-weight: 700; margin-bottom: 0.25rem;">📋 Self-Evaluation</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ประเมินตนเอง</div>
            </div>
            <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
            <div style="text-align: center; flex: 1; min-width: 150px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--secondary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">2</div>
                <div style="font-weight: 700; margin-bottom: 0.25rem;">👨‍💼 Manager Review</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">หัวหน้าประเมิน</div>
            </div>
            <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
            <div style="text-align: center; flex: 1; min-width: 150px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--accent-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">3</div>
                <div style="font-weight: 700; margin-bottom: 0.25rem;">👥 360° Feedback</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เพื่อนร่วมงาน</div>
            </div>
            <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
            <div style="text-align: center; flex: 1; min-width: 150px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">✓</div>
                <div style="font-weight: 700; margin-bottom: 0.25rem;">📊 Final Score</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">คะแนนสรุป</div>
            </div>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">📚 Training & Development</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📖 Training Plan</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Individual Development Plan (IDP)</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🎓 Training Calendar</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ปฏิทินอบรมและพัฒนา</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📊 Skills Matrix</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">แผนที่ทักษะ และช่องว่าง</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🚀 Career Path</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เส้นทางก้าวหน้าในอาชีพ</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🎯 Succession Planning</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">วางแผนผู้สืบทอดตำแหน่ง</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">💰 Budget Tracking</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ติดตามงบประมาณอบรม</p>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Performance Management Tips</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Regular 1-on-1:</strong> พูดคุยกับพนักงานอย่างน้อยเดือนละครั้ง ไม่ต้องรอถึงประเมินประจำปี</li>
            <li><strong>Continuous Feedback:</strong> ให้ Feedback ทันที ทั้งเชิงบวกและเชิงลบ อย่ารอ</li>
            <li><strong>Development Focus:</strong> ประเมินเพื่อพัฒนา ไม่ใช่เพื่อลงโทษ</li>
            <li><strong>Clear Criteria:</strong> กำหนดเกณฑ์การประเมินชัดเจน โปร่งใส ทุกคนเห็นตรงกัน</li>
            <li><strong>Recognition:</strong> ยกย่องพนักงานที่ทำผลงานดี สร้างแรงจูงใจ</li>
        </ul>
    </div>
</div>

