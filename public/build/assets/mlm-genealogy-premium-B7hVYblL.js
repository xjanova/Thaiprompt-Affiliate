class p{constructor(e,t={}){this.container=document.getElementById(e),this.options={type:t.type||"binary",memberCode:t.memberCode,apiUrl:t.apiUrl||"/api/mlm/genealogy",maxDepth:t.maxDepth||5,nodeWidth:180,nodeHeight:120,horizontalSpacing:80,verticalSpacing:100,showMinimap:!0,showStats:!0,enableSearch:!0,animationDuration:300,...t},this.data=null,this.svg=null,this.transform={x:0,y:0,k:1},this.selectedNode=null,this.highlightedNodes=new Set,this.init()}async init(){this.createContainer(),this.createControls(),await this.loadData(),this.render(),this.initializeInteractions()}createContainer(){this.container.innerHTML=`
            <div class="mlm-genealogy-premium">
                <!-- Header -->
                <div class="mlm-header">
                    <div class="mlm-header-left">
                        <h2 class="mlm-title">
                            <span class="mlm-icon">🌳</span>
                            ผังสายงาน MLM
                        </h2>
                        <div class="mlm-breadcrumb" id="mlm-breadcrumb"></div>
                    </div>
                    <div class="mlm-header-right">
                        <div class="mlm-type-switcher">
                            <button class="mlm-type-btn active" data-type="binary">
                                <span>🔀</span> Binary
                            </button>
                            <button class="mlm-type-btn" data-type="unilevel">
                                <span>📊</span> Unilevel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="mlm-toolbar">
                    <div class="mlm-search-box">
                        <input type="text" id="mlm-search" placeholder="🔍 ค้นหาสมาชิก (รหัส, ชื่อ, อีเมล...)" />
                        <div class="mlm-search-results" id="search-results"></div>
                    </div>

                    <div class="mlm-toolbar-actions">
                        <button class="mlm-btn" id="btn-expand-all" title="ขยายทั้งหมด">
                            <span>📂</span> ขยายทั้งหมด
                        </button>
                        <button class="mlm-btn" id="btn-collapse-all" title="ย่อทั้งหมด">
                            <span>📁</span> ย่อทั้งหมด
                        </button>
                        <button class="mlm-btn" id="btn-fullscreen" title="เต็มจอ">
                            <span>⛶</span> เต็มจอ
                        </button>
                        <button class="mlm-btn" id="btn-export" title="ส่งออก">
                            <span>💾</span> Export
                        </button>
                    </div>
                </div>

                <!-- Main Canvas Area -->
                <div class="mlm-canvas-wrapper">
                    <!-- SVG Canvas -->
                    <div class="mlm-canvas" id="mlm-canvas">
                        <svg id="mlm-svg"></svg>
                    </div>

                    <!-- Zoom Controls -->
                    <div class="mlm-zoom-controls">
                        <button class="mlm-zoom-btn" id="btn-zoom-in" title="ซูมเข้า">
                            <span>+</span>
                        </button>
                        <div class="mlm-zoom-level" id="zoom-level">100%</div>
                        <button class="mlm-zoom-btn" id="btn-zoom-out" title="ซูมออก">
                            <span>−</span>
                        </button>
                        <button class="mlm-zoom-btn" id="btn-zoom-reset" title="รีเซ็ต">
                            <span>⟲</span>
                        </button>
                    </div>

                    <!-- Mini Map -->
                    <div class="mlm-minimap" id="minimap">
                        <canvas id="minimap-canvas"></canvas>
                        <div class="mlm-minimap-viewport" id="minimap-viewport"></div>
                    </div>

                    <!-- Stats Panel -->
                    <div class="mlm-stats-panel" id="stats-panel">
                        <h3>📊 สถิติ</h3>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">สมาชิกทั้งหมด:</span>
                            <span class="mlm-stat-value" id="stat-total-members">0</span>
                        </div>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">สมาชิก Active:</span>
                            <span class="mlm-stat-value text-green" id="stat-active-members">0</span>
                        </div>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">PV รวม:</span>
                            <span class="mlm-stat-value text-purple" id="stat-total-pv">0</span>
                        </div>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">ความลึก:</span>
                            <span class="mlm-stat-value" id="stat-depth">0</span>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div class="mlm-loading" id="mlm-loading">
                        <div class="mlm-spinner"></div>
                        <p>กำลังโหลดข้อมูล...</p>
                    </div>
                </div>

                <!-- Node Detail Modal -->
                <div class="mlm-modal" id="node-modal">
                    <div class="mlm-modal-content">
                        <span class="mlm-modal-close" id="modal-close">&times;</span>
                        <div id="modal-body"></div>
                    </div>
                </div>
            </div>
        `,this.attachStyles()}attachStyles(){const e=document.createElement("style");e.textContent=`
            .mlm-genealogy-premium {
                width: 100%;
                height: 800px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                display: flex;
                flex-direction: column;
            }

            .mlm-header {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                padding: 20px 24px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .mlm-title {
                font-size: 24px;
                font-weight: 800;
                color: #1f2937;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .mlm-icon {
                font-size: 32px;
            }

            .mlm-breadcrumb {
                font-size: 14px;
                color: #6b7280;
                margin-top: 4px;
            }

            .mlm-type-switcher {
                display: flex;
                gap: 8px;
                background: #f3f4f6;
                padding: 4px;
                border-radius: 12px;
            }

            .mlm-type-btn {
                padding: 8px 16px;
                border: none;
                background: transparent;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                color: #6b7280;
                transition: all 0.2s;
            }

            .mlm-type-btn.active {
                background: white;
                color: #7c3aed;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .mlm-toolbar {
                background: rgba(255, 255, 255, 0.9);
                padding: 16px 24px;
                display: flex;
                gap: 16px;
                align-items: center;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .mlm-search-box {
                flex: 1;
                position: relative;
            }

            .mlm-search-box input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                font-size: 14px;
                transition: all 0.2s;
            }

            .mlm-search-box input:focus {
                outline: none;
                border-color: #7c3aed;
                box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            }

            .mlm-search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                max-height: 300px;
                overflow-y: auto;
                margin-top: 8px;
                z-index: 10;
                display: none;
            }

            .mlm-search-results.show {
                display: block;
            }

            .mlm-search-item {
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid #f3f4f6;
                transition: background 0.2s;
            }

            .mlm-search-item:hover {
                background: #f9fafb;
            }

            .mlm-toolbar-actions {
                display: flex;
                gap: 8px;
            }

            .mlm-btn {
                padding: 8px 16px;
                border: 2px solid #e5e7eb;
                background: white;
                border-radius: 10px;
                cursor: pointer;
                font-weight: 600;
                font-size: 13px;
                color: #374151;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .mlm-btn:hover {
                border-color: #7c3aed;
                color: #7c3aed;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
            }

            .mlm-canvas-wrapper {
                flex: 1;
                position: relative;
                overflow: hidden;
            }

            .mlm-canvas {
                width: 100%;
                height: 100%;
                position: relative;
                overflow: hidden;
                cursor: grab;
            }

            .mlm-canvas:active {
                cursor: grabbing;
            }

            #mlm-svg {
                width: 100%;
                height: 100%;
            }

            .mlm-zoom-controls {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                padding: 8px;
                display: flex;
                flex-direction: column;
                gap: 4px;
                z-index: 5;
            }

            .mlm-zoom-btn {
                width: 40px;
                height: 40px;
                border: none;
                background: white;
                border-radius: 8px;
                cursor: pointer;
                font-size: 20px;
                font-weight: bold;
                color: #7c3aed;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mlm-zoom-btn:hover {
                background: #7c3aed;
                color: white;
                transform: scale(1.1);
            }

            .mlm-zoom-level {
                text-align: center;
                font-size: 12px;
                font-weight: 600;
                color: #6b7280;
                padding: 4px;
            }

            .mlm-minimap {
                position: absolute;
                bottom: 20px;
                right: 20px;
                width: 200px;
                height: 150px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                overflow: hidden;
                z-index: 5;
            }

            #minimap-canvas {
                width: 100%;
                height: 100%;
            }

            .mlm-minimap-viewport {
                position: absolute;
                border: 2px solid #7c3aed;
                background: rgba(124, 58, 237, 0.1);
                pointer-events: none;
            }

            .mlm-stats-panel {
                position: absolute;
                bottom: 20px;
                left: 20px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                padding: 16px;
                min-width: 200px;
                z-index: 5;
            }

            .mlm-stats-panel h3 {
                margin: 0 0 12px 0;
                font-size: 16px;
                font-weight: 700;
                color: #1f2937;
            }

            .mlm-stat-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 13px;
            }

            .mlm-stat-label {
                color: #6b7280;
            }

            .mlm-stat-value {
                font-weight: 700;
                color: #1f2937;
            }

            .mlm-stat-value.text-green {
                color: #10b981;
            }

            .mlm-stat-value.text-purple {
                color: #7c3aed;
            }

            /* Node Styles */
            .mlm-node {
                cursor: pointer;
                transition: all 0.3s;
            }

            .mlm-node:hover .mlm-node-card {
                transform: scale(1.05);
                filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.2));
            }

            .mlm-node-card {
                transition: all 0.3s;
            }

            .mlm-node-active .mlm-node-card {
                filter: drop-shadow(0 4px 15px rgba(16, 185, 129, 0.5));
            }

            .mlm-node-inactive .mlm-node-card {
                opacity: 0.6;
            }

            .mlm-node-selected .mlm-node-card {
                filter: drop-shadow(0 0 20px rgba(124, 58, 237, 0.8));
            }

            .mlm-connection {
                stroke: #9ca3af;
                stroke-width: 2;
                fill: none;
                transition: all 0.3s;
            }

            .mlm-connection-highlight {
                stroke: #7c3aed;
                stroke-width: 3;
            }

            /* Loading */
            .mlm-loading {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.95);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 20;
            }

            .mlm-spinner {
                width: 50px;
                height: 50px;
                border: 4px solid #e5e7eb;
                border-top-color: #7c3aed;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            /* Modal */
            .mlm-modal {
                display: none;
                position: fixed;
                z-index: 100;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(5px);
            }

            .mlm-modal.show {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mlm-modal-content {
                background: white;
                border-radius: 16px;
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                position: relative;
                padding: 24px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }

            .mlm-modal-close {
                position: absolute;
                top: 16px;
                right: 16px;
                font-size: 28px;
                font-weight: bold;
                color: #9ca3af;
                cursor: pointer;
                transition: color 0.2s;
            }

            .mlm-modal-close:hover {
                color: #1f2937;
            }
        `,document.head.appendChild(e)}createControls(){this.container.querySelector("#btn-zoom-in").addEventListener("click",()=>this.zoomIn()),this.container.querySelector("#btn-zoom-out").addEventListener("click",()=>this.zoomOut()),this.container.querySelector("#btn-zoom-reset").addEventListener("click",()=>this.resetView()),this.container.querySelectorAll(".mlm-type-btn").forEach(t=>{t.addEventListener("click",()=>{this.container.querySelectorAll(".mlm-type-btn").forEach(i=>i.classList.remove("active")),t.classList.add("active"),this.switchType(t.dataset.type)})}),this.container.querySelector("#mlm-search").addEventListener("input",t=>this.handleSearch(t.target.value)),this.container.querySelector("#btn-fullscreen").addEventListener("click",()=>this.toggleFullscreen()),this.container.querySelector("#modal-close").addEventListener("click",()=>this.closeModal())}async loadData(){const e=this.container.querySelector("#mlm-loading");e.style.display="flex";try{const i=await(await fetch(`${this.options.apiUrl}?type=${this.options.type}&depth=${this.options.maxDepth}`)).json();this.data=i.data,this.updateStats()}catch(t){console.error("Error loading genealogy data:",t),this.data=this.generateDemoData()}finally{e.style.display="none"}}generateDemoData(){return{id:1,name:"John Doe",member_code:"MLM001",email:"john@example.com",status:"active",total_pv:1e4,direct_referrals:2,total_team_members:15,left:{id:2,name:"Alice Smith",member_code:"MLM002",status:"active",total_pv:5e3,direct_referrals:2,left:{id:4,name:"Bob Johnson",member_code:"MLM004",status:"active",total_pv:2e3},right:{id:5,name:"Carol Williams",member_code:"MLM005",status:"inactive",total_pv:1500}},right:{id:3,name:"David Brown",member_code:"MLM003",status:"active",total_pv:3e3,direct_referrals:1,left:{id:6,name:"Eve Davis",member_code:"MLM006",status:"active",total_pv:2500}}}}render(){const e=this.container.querySelector("#mlm-svg");e.innerHTML="";const t=document.createElementNS("http://www.w3.org/2000/svg","g");t.setAttribute("id","main-group"),e.appendChild(t),this.options.type==="binary"?this.renderBinaryTree(this.data,400,50,t):this.renderUnilevelTree(this.data,400,50,t),this.resetView()}renderBinaryTree(e,t,i,s,a=0){if(!e||a>this.options.maxDepth)return;const r=this.options.horizontalSpacing/Math.pow(1.5,a);if(e.left){const o=t-r*100,n=i+this.options.verticalSpacing;this.drawConnection(s,t,i+this.options.nodeHeight/2,o,n,"left"),this.renderBinaryTree(e.left,o,n,s,a+1)}if(e.right){const o=t+r*100,n=i+this.options.verticalSpacing;this.drawConnection(s,t,i+this.options.nodeHeight/2,o,n,"right"),this.renderBinaryTree(e.right,o,n,s,a+1)}this.drawNode(s,e,t,i)}renderUnilevelTree(e,t,i,s,a=0){if(!e||a>this.options.maxDepth)return;this.drawNode(s,e,t,i);const r=e.children||[];if(r.length===0)return;const o=r.length*(this.options.nodeWidth+this.options.horizontalSpacing),n=t-o/2+this.options.nodeWidth/2,l=i+this.options.verticalSpacing;r.forEach((d,c)=>{const m=n+c*(this.options.nodeWidth+this.options.horizontalSpacing);this.drawConnection(s,t,i+this.options.nodeHeight,m,l),this.renderUnilevelTree(d,m,l,s,a+1)})}drawNode(e,t,i,s){const a=document.createElementNS("http://www.w3.org/2000/svg","g");a.setAttribute("class",`mlm-node mlm-node-${t.retention_status||t.status}`),a.setAttribute("data-node-id",t.id),a.setAttribute("transform",`translate(${i-this.options.nodeWidth/2}, ${s})`);const r=t.retention_status||t.status;let o,n,l,d;switch(r){case"active":o="linear-gradient(135deg, #10b981 0%, #059669 100%)",n="#10b981",l="#dcfce7",d="#059669";break;case"grace_period":o="linear-gradient(135deg, #f59e0b 0%, #d97706 100%)",n="#f59e0b",l="#fef3c7",d="#d97706";break;case"inactive":o="linear-gradient(135deg, #ef4444 0%, #dc2626 100%)",n="#ef4444",l="#fee2e2",d="#dc2626";break;default:o="linear-gradient(135deg, #6b7280 0%, #4b5563 100%)",n="#6b7280",l="#f3f4f6",d="#6b7280"}a.innerHTML=`
            <foreignObject width="${this.options.nodeWidth}" height="${this.options.nodeHeight}">
                <div xmlns="http://www.w3.org/1999/xhtml" class="mlm-node-card" style="
                    width: 100%;
                    height: 100%;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    font-family: system-ui, -apple-system, sans-serif;
                    border: 3px solid ${n};
                ">
                    <div style="
                        background: ${o};
                        padding: 12px;
                        color: white;
                    ">
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">
                            ${t.name||"Unknown"}
                        </div>
                        <div style="font-size: 11px; opacity: 0.9;">
                            ${t.member_code}
                        </div>
                    </div>
                    <div style="padding: 12px; font-size: 11px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #6b7280;">PV:</span>
                            <span style="font-weight: 700; color: #7c3aed;">${t.monthly_pv||t.total_pv||0}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #6b7280;">Refs:</span>
                            <span style="font-weight: 700;">${t.direct_referrals||0}</span>
                        </div>
                        <div style="
                            background: ${l};
                            color: ${d};
                            text-align: center;
                            padding: 4px;
                            border-radius: 6px;
                            font-size: 10px;
                            font-weight: 600;
                            text-transform: uppercase;
                        ">
                            ${r==="grace_period"?"⚠️ Grace":r==="inactive"?"❌ Inactive":"✓ Active"}
                        </div>
                    </div>
                </div>
            </foreignObject>
        `,a.addEventListener("click",()=>this.showNodeDetails(t)),e.appendChild(a)}drawConnection(e,t,i,s,a,r=null){const o=document.createElementNS("http://www.w3.org/2000/svg","path"),n=(i+a)/2,l=`M ${t} ${i} C ${t} ${n}, ${s} ${n}, ${s} ${a}`;o.setAttribute("d",l),o.setAttribute("class","mlm-connection"),o.setAttribute("stroke-width","2"),o.setAttribute("fill","none"),r==="left"?o.setAttribute("stroke","#10b981"):r==="right"?o.setAttribute("stroke","#ef4444"):o.setAttribute("stroke","#9ca3af"),e.insertBefore(o,e.firstChild)}initializeInteractions(){const e=this.container.querySelector("#mlm-svg"),t=this.container.querySelector("#mlm-canvas");let i=!1,s={x:0,y:0};t.addEventListener("mousedown",a=>{(a.target===e||a.target===t)&&(i=!0,s={x:a.clientX-this.transform.x,y:a.clientY-this.transform.y})}),t.addEventListener("mousemove",a=>{i&&(this.transform.x=a.clientX-s.x,this.transform.y=a.clientY-s.y,this.applyTransform())}),t.addEventListener("mouseup",()=>{i=!1}),t.addEventListener("mouseleave",()=>{i=!1}),t.addEventListener("wheel",a=>{a.preventDefault();const r=a.deltaY>0?.9:1.1;this.transform.k*=r,this.transform.k=Math.max(.1,Math.min(3,this.transform.k)),this.applyTransform(),this.updateZoomLevel()})}applyTransform(){const e=this.container.querySelector("#main-group");e&&e.setAttribute("transform",`translate(${this.transform.x}, ${this.transform.y}) scale(${this.transform.k})`)}zoomIn(){this.transform.k*=1.2,this.transform.k=Math.min(3,this.transform.k),this.applyTransform(),this.updateZoomLevel()}zoomOut(){this.transform.k*=.8,this.transform.k=Math.max(.1,this.transform.k),this.applyTransform(),this.updateZoomLevel()}resetView(){this.transform={x:200,y:50,k:1},this.applyTransform(),this.updateZoomLevel()}updateZoomLevel(){const e=this.container.querySelector("#zoom-level");e&&(e.textContent=Math.round(this.transform.k*100)+"%")}updateStats(){const e=this.calculateStats(this.data);this.container.querySelector("#stat-total-members").textContent=e.totalMembers,this.container.querySelector("#stat-active-members").textContent=e.activeMembers,this.container.querySelector("#stat-total-pv").textContent=e.totalPv.toLocaleString(),this.container.querySelector("#stat-depth").textContent=e.maxDepth}calculateStats(e,t={totalMembers:0,activeMembers:0,totalPv:0,maxDepth:0},i=0){return e&&(t.totalMembers++,e.status==="active"&&t.activeMembers++,t.totalPv+=e.total_pv||0,t.maxDepth=Math.max(t.maxDepth,i),this.options.type==="binary"?(e.left&&this.calculateStats(e.left,t,i+1),e.right&&this.calculateStats(e.right,t,i+1)):(e.children||[]).forEach(s=>this.calculateStats(s,t,i+1))),t}handleSearch(e){const t=this.container.querySelector("#search-results");if(!e){t.classList.remove("show");return}t.innerHTML=`
            <div class="mlm-search-item">
                <strong>MLM001</strong> - John Doe<br>
                <small style="color: #6b7280;">john@example.com</small>
            </div>
        `,t.classList.add("show")}showNodeDetails(e){const t=this.container.querySelector("#node-modal"),i=this.container.querySelector("#modal-body");i.innerHTML=`
            <h2 style="margin: 0 0 20px 0; color: #1f2937;">📋 รายละเอียดสมาชิก</h2>
            <div style="background: #f9fafb; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <div style="font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">
                    ${e.name||"Unknown"}
                </div>
                <div style="color: #6b7280; font-size: 14px;">
                    ${e.member_code} | ${e.email||"No email"}
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
                <div style="background: #ecfdf5; padding: 16px; border-radius: 12px;">
                    <div style="color: #059669; font-size: 12px; font-weight: 600; margin-bottom: 8px;">TOTAL PV</div>
                    <div style="font-size: 24px; font-weight: 700; color: #047857;">${e.total_pv||0}</div>
                </div>
                <div style="background: #ede9fe; padding: 16px; border-radius: 12px;">
                    <div style="color: #7c3aed; font-size: 12px; font-weight: 600; margin-bottom: 8px;">DIRECT REFS</div>
                    <div style="font-size: 24px; font-weight: 700; color: #6d28d9;">${e.direct_referrals||0}</div>
                </div>
            </div>
            <div style="text-align: center;">
                <button onclick="window.location.href='/admin/mlm/members/${e.id}'"
                        style="
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
                            color: white;
                            border: none;
                            border-radius: 12px;
                            font-weight: 600;
                            cursor: pointer;
                        ">
                    ดูรายละเอียดเพิ่มเติม
                </button>
            </div>
        `,t.classList.add("show")}closeModal(){this.container.querySelector("#node-modal").classList.remove("show")}toggleFullscreen(){document.fullscreenElement?document.exitFullscreen():this.container.requestFullscreen()}switchType(e){this.options.type=e,this.loadData().then(()=>this.render())}}window.MlmGenealogyPremium=p;
