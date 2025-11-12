{{-- Hero Section --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 3rem 2rem; border-radius: 20px; margin-bottom: 3rem; color: white; text-align: center;">
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">🎓 Academy & Learning Management System (LMS)</h1>
    <p style="font-size: 1.25rem; opacity: 0.95; max-width: 800px; margin: 0 auto;">ระบบเรียนรู้ออนไลน์แบบครบวงจร พร้อม Gamification เพิ่มแรงจูงใจและประกาศนียบัตรดิจิทัล</p>
</div>

{{-- Tab Navigation --}}
<div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--wiki-border); margin-bottom: 2rem; flex-wrap: wrap;">
    <button class="wiki-tab active" data-tab="courses" style="padding: 0.75rem 1.5rem; border: none; background: rgb(var(--primary-rgb)); color: white; font-weight: 600; border-radius: 8px 8px 0 0; cursor: pointer; transition: all 0.3s;">
        📚 Courses & Content
    </button>
    <button class="wiki-tab" data-tab="gamification" style="padding: 0.75rem 1.5rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); font-weight: 600; border-radius: 8px 8px 0 0; cursor: pointer; transition: all 0.3s;">
        🏆 Gamification
    </button>
    <button class="wiki-tab" data-tab="certificates" style="padding: 0.75rem 1.5rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); font-weight: 600; border-radius: 8px 8px 0 0; cursor: pointer; transition: all 0.3s;">
        📜 Certificates
    </button>
    <button class="wiki-tab" data-tab="analytics" style="padding: 0.75rem 1.5rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); font-weight: 600; border-radius: 8px 8px 0 0; cursor: pointer; transition: all 0.3s;">
        📊 Analytics & Reports
    </button>
</div>

{{-- Tab 1: Courses & Content --}}
<div class="wiki-tab-content active" data-tab-content="courses">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">📚 Learning Content & Courses</h2>

    <div class="info-box success">
        <h4>🎯 ระบบจัดการเนื้อหาการเรียนรู้</h4>
        <p>สร้างคอร์สเรียนออนไลน์ที่หลากหลาย ตั้งแต่วิดีโอบทเรียน บทความ แบบทดสอบ จนถึงคลาสสด</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🎬 Content Types</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎥</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Video Lessons</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ HD Video Streaming</li>
                <li>✅ Progress Tracking</li>
                <li>✅ Playback Speed Control</li>
                <li>✅ Subtitles/Captions</li>
                <li>✅ Mobile Optimized</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📖</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Articles & Documents</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Rich Text Editor</li>
                <li>✅ PDF Upload</li>
                <li>✅ Image & Diagrams</li>
                <li>✅ Code Snippets</li>
                <li>✅ Downloadable Resources</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✍️</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Quiz & Exams</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Multiple Choice</li>
                <li>✅ True/False</li>
                <li>✅ Fill in the Blank</li>
                <li>✅ Essay Questions</li>
                <li>✅ Auto Grading</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='#FF9800'; this.style.boxShadow='0 8px 24px rgba(255, 152, 0, 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📡</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Live Classes</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Zoom/Google Meet Integration</li>
                <li>✅ Schedule Management</li>
                <li>✅ Auto Recording</li>
                <li>✅ Q&A Session</li>
                <li>✅ Attendance Tracking</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='#9C27B0'; this.style.boxShadow='0 8px 24px rgba(156, 39, 176, 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Assignments</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ File Upload</li>
                <li>✅ Due Date & Reminders</li>
                <li>✅ Peer Review</li>
                <li>✅ Grading Rubric</li>
                <li>✅ Feedback System</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='#00BCD4'; this.style.boxShadow='0 8px 24px rgba(0, 188, 212, 0.2)'; this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Discussion Forums</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Topic Threads</li>
                <li>✅ Upvote/Downvote</li>
                <li>✅ Instructor Moderation</li>
                <li>✅ Notifications</li>
                <li>✅ Search & Filter</li>
            </ul>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Course Creation Best Practices</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Microlearning:</strong> แบ่งเนื้อหาเป็นบทสั้นๆ 5-10 นาที เรียนรู้ง่าย จดจำได้ดี</li>
            <li><strong>Mix Content:</strong> ผสม Video, Text, Quiz เพื่อความหลากหลาย</li>
            <li><strong>Progressive Difficulty:</strong> เริ่มจากง่าย ค่อยๆ ยากขึ้น</li>
            <li><strong>Real-world Examples:</strong> ใช้ตัวอย่างจากการทำงานจริง</li>
        </ul>
    </div>
</div>

{{-- Tab 2: Gamification --}}
<div class="wiki-tab-content" data-tab-content="gamification" style="display: none;">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">🏆 Gamification System</h2>

    <div class="info-box success">
        <h4>🎮 เพิ่มแรงจูงใจด้วยเกมมิฟิเคชั่น</h4>
        <p>สร้างความสนุกในการเรียนรู้ด้วยระบบแต้ม, เหรียญรางวัล, ลีดเดอร์บอร์ด และความท้าทาย</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🎁 Reward System</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⭐</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Points System</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">สะสมแต้มจากการทำกิจกรรม</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>🎯 เรียนจบ 1 บท: +10 แต้ม</li>
                <li>🎯 ผ่าน Quiz: +20 แต้ม</li>
                <li>🎯 ครบ 100%: +100 แต้ม</li>
                <li>🎯 เรียนต่อเนื่อง 7 วัน: +50 แต้ม</li>
            </ul>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🏅</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">Achievement Badges</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">ปลดล็อกเหรียญรางวัล</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>🥉 Beginner: จบ 1 คอร์ส</li>
                <li>🥈 Intermediate: จบ 5 คอร์ส</li>
                <li>🥇 Expert: จบ 20 คอร์ส</li>
                <li>💎 Master: 100% ทุกคอร์ส</li>
            </ul>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 12px; padding: 1.5rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">Leaderboard</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">แข่งขันกับผู้เรียนอื่น</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>🏆 Top 10 Daily</li>
                <li>🏆 Top 20 Weekly</li>
                <li>🏆 Top 50 Monthly</li>
                <li>🏆 Hall of Fame (All-time)</li>
            </ul>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🎯 Quests & Challenges</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border-left: 4px solid #FFD700; padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📅 Daily Quest</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เรียน 1 บท/วัน รับ 5 แต้ม</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid #C0C0C0; padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📆 Weekly Challenge</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">จบ 1 คอร์ส/สัปดาห์ รับ Badge</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid #CD7F32; padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🗓️ Monthly Mission</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">จบ 5 คอร์ส รับของรางวัล</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">🔥 Streak Bonus</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เรียนต่อเนื่อง x2 แต้ม</p>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Gamification Tips</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Start Small:</strong> รางวัลง่ายๆ ในช่วงแรก สร้างแรงจูงใจ</li>
            <li><strong>Progress Visible:</strong> แสดง Progress Bar ให้เห็นความคืบหน้าชัดเจน</li>
            <li><strong>Social Sharing:</strong> ให้แชร์ Badge ไปโซเชียลได้</li>
            <li><strong>Fair Competition:</strong> แบ่ง Leaderboard ตามระดับ</li>
        </ul>
    </div>
</div>

{{-- Tab 3: Certificates --}}
<div class="wiki-tab-content" data-tab-content="certificates" style="display: none;">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">📜 Digital Certificates</h2>

    <div class="info-box success">
        <h4>🎓 ประกาศนียบัตรดิจิทัลอัตโนมัติ</h4>
        <p>สร้างและออกประกาศนียบัตรอัตโนมัติเมื่อผู้เรียนผ่านคอร์ส พร้อมระบบตรวจสอบความถูกต้อง</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🎨 Certificate Features</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🎨</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Custom Templates</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Drag & Drop Designer</li>
                <li>✅ Multiple Templates</li>
                <li>✅ Company Logo/Brand</li>
                <li>✅ Custom Colors</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">✍️</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Digital Signature</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ E-Signature Support</li>
                <li>✅ QR Code Verification</li>
                <li>✅ Blockchain Integration</li>
                <li>✅ Tamper-proof</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔍</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Verification System</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Unique Certificate ID</li>
                <li>✅ Public Verification Page</li>
                <li>✅ LinkedIn Integration</li>
                <li>✅ API Access</li>
            </ul>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
             onmouseover="this.style.borderColor='#FF9800'; this.style.boxShadow='0 8px 24px rgba(255, 152, 0, 0.2)'"
             onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">📧</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Auto Delivery</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Email Notification</li>
                <li>✅ PDF Download</li>
                <li>✅ Print-ready</li>
                <li>✅ Social Sharing</li>
            </ul>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Certificate Best Practices</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Professional Design:</strong> ใช้เทมเพลตที่ดูมืออาชีพ มีน้ำหนัก</li>
            <li><strong>Clear Information:</strong> ระบุชื่อคอร์ส, ชื่อผู้เรียน, วันที่ชัดเจน</li>
            <li><strong>Verification QR:</strong> ใส่ QR Code สำหรับตรวจสอบความถูกต้อง</li>
            <li><strong>Blockchain Proof:</strong> บันทึกบน Blockchain เพิ่มความน่าเชื่อถือ</li>
        </ul>
    </div>
</div>

{{-- Tab 4: Analytics & Reports --}}
<div class="wiki-tab-content" data-tab-content="analytics" style="display: none;">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">📊 Learning Analytics & Reports</h2>

    <div class="info-box success">
        <h4>📈 วิเคราะห์ข้อมูลการเรียนรู้</h4>
        <p>ติดตามความคืบหน้า วิเคราะห์พฤติกรรมการเรียน และรายงานผลการเรียนรู้แบบ Real-time</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">📊 Key Metrics</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Enrollment Rate</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">85%</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">อัตราการลงทะเบียนเรียน</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Completion Rate</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb)); margin: 0;">72%</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">อัตราการเรียนจบคอร์ส</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Avg. Score</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb)); margin: 0;">78%</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">คะแนนเฉลี่ย</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Engagement</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">4.5h</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">เวลาเรียนเฉลี่ย/สัปดาห์</p>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">📋 Report Types</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">👤 Individual Progress</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ความคืบหน้าแต่ละคน, คะแนน, เวลาเรียน</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">👥 Group Performance</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เปรียบเทียบกลุ่ม, แผนก, ทีม</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📚 Course Analytics</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ยอดนิยม, Drop-off rate, Engagement</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📊 Skills Gap Analysis</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">วิเคราะห์ช่องว่างทักษะที่ต้องพัฒนา</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">💰 ROI Reports</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ผลตอบแทนจากการลงทุนด้านการเรียนรู้</p>
        </div>
        <div style="background: var(--wiki-card-bg); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1rem; border-radius: 8px;">
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">📈 Trend Analysis</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">แนวโน้มการเรียนรู้ช่วงเวลาต่างๆ</p>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Analytics Best Practices</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Early Warning System:</strong> แจ้งเตือนเมื่อผู้เรียนมีสัญญาณจะ Drop out</li>
            <li><strong>Personalized Recommendations:</strong> แนะนำคอร์สตามทักษะที่ขาด</li>
            <li><strong>A/B Testing:</strong> ทดสอบเนื้อหาแบบต่างๆ ดูว่าแบบไหนได้ผลดี</li>
            <li><strong>Regular Review:</strong> Review ข้อมูลทุกเดือน ปรับปรุงคอร์สอยู่เสมอ</li>
        </ul>
    </div>
</div>

<script>
// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.wiki-tab');
    const tabContents = document.querySelectorAll('.wiki-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'var(--wiki-card-bg)';
                btn.style.color = 'var(--wiki-text)';
            });
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });

            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            button.style.background = 'rgb(var(--primary-rgb))';
            button.style.color = 'white';

            const targetContent = document.querySelector(`[data-tab-content="${targetTab}"]`);
            if (targetContent) {
                targetContent.classList.add('active');
                targetContent.style.display = 'block';
            }
        });
    });
});
</script>
