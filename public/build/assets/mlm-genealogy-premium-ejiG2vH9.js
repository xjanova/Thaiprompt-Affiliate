class u{constructor(t,e={}){this.container=document.getElementById(t),this.options={type:e.type||"binary",memberCode:e.memberCode,apiUrl:e.apiUrl||"/api/mlm/genealogy",maxDepth:e.maxDepth||5,nodeWidth:180,nodeHeight:120,horizontalSpacing:80,verticalSpacing:100,showMinimap:!0,showStats:!0,enableSearch:!0,animationDuration:300,...e},this.data=null,this.svg=null,this.transform={x:0,y:0,k:1},this.selectedNode=null,this.highlightedNodes=new Set,this.init()}async init(){this.createContainer(),this.createControls(),await this.loadData(),this.render(),this.initializeInteractions()}createContainer(){this.container.innerHTML=`
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
        `,this.attachStyles()}attachStyles(){const t=document.createElement("style");t.textContent=`
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
                cursor: pointer;
            }

            .mlm-minimap-viewport {
                position: absolute;
                border: 2px solid #7c3aed;
                background: rgba(124, 58, 237, 0.1);
                pointer-events: none;
                transition: left 0.15s ease, top 0.15s ease;
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
        `,document.head.appendChild(t)}createControls(){this.container.querySelector("#btn-zoom-in").addEventListener("click",()=>this.zoomIn()),this.container.querySelector("#btn-zoom-out").addEventListener("click",()=>this.zoomOut()),this.container.querySelector("#btn-zoom-reset").addEventListener("click",()=>this.resetView()),this.container.querySelectorAll(".mlm-type-btn").forEach(e=>{e.addEventListener("click",()=>{this.container.querySelectorAll(".mlm-type-btn").forEach(i=>i.classList.remove("active")),e.classList.add("active"),this.switchType(e.dataset.type)})}),this.container.querySelector("#mlm-search").addEventListener("input",e=>this.handleSearch(e.target.value)),this.container.querySelector("#btn-fullscreen").addEventListener("click",()=>this.toggleFullscreen()),this.container.querySelector("#modal-close").addEventListener("click",()=>this.closeModal())}async loadData(){const t=this.container.querySelector("#mlm-loading");t.style.display="flex";try{const e=await fetch(`${this.options.apiUrl}?type=${this.options.type}&depth=${this.options.maxDepth}`);if(!e.ok)throw new Error(`HTTP ${e.status}: ${e.statusText}`);const i=await e.json();if(!i.success||!i.data)throw new Error(i.message||"ไม่สามารถโหลดข้อมูลผังสายงานได้");this.data=i.data,this.updateStats()}catch(e){console.error("Error loading genealogy data:",e);const i=document.createElement("div");i.className="flex flex-col items-center justify-center p-8 text-center",i.innerHTML=`
                <div class="text-5xl mb-4">⚠️</div>
                <div class="text-lg font-bold text-gray-800 dark:text-white mb-2">โหลดผังสายงานไม่สำเร็จ</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">${e.message}</div>
                <button onclick="location.reload()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm transition">
                    ลองใหม่
                </button>
            `;const s=this.container.querySelector("#mlm-canvas")||this.container.querySelector("svg");s&&(s.parentNode.insertBefore(i,s),s.style.display="none"),this.data=null}finally{t.style.display="none"}}generateDemoData(){return{id:1,name:"John Doe",member_code:"MLM001",email:"john@example.com",status:"active",total_pv:1e4,direct_referrals:2,total_team_members:15,left:{id:2,name:"Alice Smith",member_code:"MLM002",status:"active",total_pv:5e3,direct_referrals:2,left:{id:4,name:"Bob Johnson",member_code:"MLM004",status:"active",total_pv:2e3},right:{id:5,name:"Carol Williams",member_code:"MLM005",status:"inactive",total_pv:1500}},right:{id:3,name:"David Brown",member_code:"MLM003",status:"active",total_pv:3e3,direct_referrals:1,left:{id:6,name:"Eve Davis",member_code:"MLM006",status:"active",total_pv:2500}}}}render(){const t=this.container.querySelector("#mlm-svg");t.innerHTML="";const e=document.createElementNS("http://www.w3.org/2000/svg","g");e.setAttribute("id","main-group"),t.appendChild(e),this.options.type==="binary"?this.renderBinaryTree(this.data,400,50,e):this.renderUnilevelTree(this.data,400,50,e),this.resetView()}renderBinaryTree(t,e,i,s,n=0){if(!t||n>this.options.maxDepth)return;const o=this.options.horizontalSpacing/Math.pow(1.5,n);if(t.left){const r=e-o*100,a=i+this.options.verticalSpacing;this.drawConnection(s,e,i+this.options.nodeHeight/2,r,a,"left"),this.renderBinaryTree(t.left,r,a,s,n+1)}if(t.right){const r=e+o*100,a=i+this.options.verticalSpacing;this.drawConnection(s,e,i+this.options.nodeHeight/2,r,a,"right"),this.renderBinaryTree(t.right,r,a,s,n+1)}this.drawNode(s,t,e,i)}renderUnilevelTree(t,e,i,s,n=0){if(!t||n>this.options.maxDepth)return;this.drawNode(s,t,e,i);const o=t.children||[];if(o.length===0)return;const r=o.length*(this.options.nodeWidth+this.options.horizontalSpacing),a=e-r/2+this.options.nodeWidth/2,l=i+this.options.verticalSpacing;o.forEach((d,c)=>{const m=a+c*(this.options.nodeWidth+this.options.horizontalSpacing);this.drawConnection(s,e,i+this.options.nodeHeight,m,l),this.renderUnilevelTree(d,m,l,s,n+1)})}drawNode(t,e,i,s){const n=document.createElementNS("http://www.w3.org/2000/svg","g");n.setAttribute("class",`mlm-node mlm-node-${e.retention_status||e.status}`),n.setAttribute("data-node-id",e.id),n.setAttribute("transform",`translate(${i-this.options.nodeWidth/2}, ${s})`);const o=e.retention_status||e.status;let r,a,l,d;switch(o){case"active":r="linear-gradient(135deg, #10b981 0%, #059669 100%)",a="#10b981",l="#dcfce7",d="#059669";break;case"grace_period":r="linear-gradient(135deg, #f59e0b 0%, #d97706 100%)",a="#f59e0b",l="#fef3c7",d="#d97706";break;case"inactive":r="linear-gradient(135deg, #ef4444 0%, #dc2626 100%)",a="#ef4444",l="#fee2e2",d="#dc2626";break;default:r="linear-gradient(135deg, #6b7280 0%, #4b5563 100%)",a="#6b7280",l="#f3f4f6",d="#6b7280"}n.innerHTML=`
            <foreignObject width="${this.options.nodeWidth}" height="${this.options.nodeHeight}">
                <div xmlns="http://www.w3.org/1999/xhtml" class="mlm-node-card" style="
                    width: 100%;
                    height: 100%;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    font-family: system-ui, -apple-system, sans-serif;
                    border: 3px solid ${a};
                ">
                    <div style="
                        background: ${r};
                        padding: 12px;
                        color: white;
                    ">
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">
                            ${e.name||"Unknown"}
                        </div>
                        <div style="font-size: 11px; opacity: 0.9;">
                            ${e.member_code}
                        </div>
                    </div>
                    <div style="padding: 12px; font-size: 11px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #6b7280;">PV:</span>
                            <span style="font-weight: 700; color: #7c3aed;">${e.monthly_pv||e.total_pv||0}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #6b7280;">Refs:</span>
                            <span style="font-weight: 700;">${e.direct_referrals||0}</span>
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
                            ${o==="grace_period"?"⚠️ Grace":o==="inactive"?"❌ Inactive":"✓ Active"}
                        </div>
                    </div>
                </div>
            </foreignObject>
        `,n.addEventListener("click",c=>{c.stopPropagation(),!this.hasDragged&&this.showNodeDetails(e)}),t.appendChild(n)}drawConnection(t,e,i,s,n,o=null){const r=document.createElementNS("http://www.w3.org/2000/svg","path"),a=(i+n)/2,l=`M ${e} ${i} C ${e} ${a}, ${s} ${a}, ${s} ${n}`;r.setAttribute("d",l),r.setAttribute("class","mlm-connection"),r.setAttribute("stroke-width","2"),r.setAttribute("fill","none"),o==="left"?r.setAttribute("stroke","#10b981"):o==="right"?r.setAttribute("stroke","#ef4444"):r.setAttribute("stroke","#9ca3af"),t.insertBefore(r,t.firstChild)}initializeInteractions(){this.container.querySelector("#mlm-svg");const t=this.container.querySelector("#mlm-canvas");this.isPanning=!1,this.hasDragged=!1,this.dragThreshold=5,this.mouseDownPoint={x:0,y:0};let e={x:0,y:0};t.addEventListener("mousedown",i=>{i.button===0&&(this.isPanning=!0,this.hasDragged=!1,this.mouseDownPoint={x:i.clientX,y:i.clientY},e={x:i.clientX-this.transform.x,y:i.clientY-this.transform.y},t.style.cursor="grabbing",i.preventDefault())}),document.addEventListener("mousemove",i=>{if(!this.isPanning)return;const s=i.clientX-this.mouseDownPoint.x,n=i.clientY-this.mouseDownPoint.y;Math.sqrt(s*s+n*n)>this.dragThreshold&&(this.hasDragged=!0),this.transform.x=i.clientX-e.x,this.transform.y=i.clientY-e.y,this.applyTransform(),this.updateMinimap()}),document.addEventListener("mouseup",()=>{this.isPanning&&(this.isPanning=!1,t.style.cursor="grab")}),t.addEventListener("touchstart",i=>{if(i.touches.length===1){const s=i.touches[0];this.isPanning=!0,this.hasDragged=!1,this.mouseDownPoint={x:s.clientX,y:s.clientY},e={x:s.clientX-this.transform.x,y:s.clientY-this.transform.y}}},{passive:!0}),t.addEventListener("touchmove",i=>{if(this.isPanning&&i.touches.length===1){const s=i.touches[0],n=s.clientX-this.mouseDownPoint.x,o=s.clientY-this.mouseDownPoint.y;Math.sqrt(n*n+o*o)>this.dragThreshold&&(this.hasDragged=!0),this.transform.x=s.clientX-e.x,this.transform.y=s.clientY-e.y,this.applyTransform(),this.updateMinimap()}},{passive:!0}),t.addEventListener("touchend",()=>{this.isPanning=!1}),t.addEventListener("wheel",i=>{i.preventDefault();const s=t.getBoundingClientRect(),n=i.clientX-s.left,o=i.clientY-s.top,r=i.deltaY>0?.9:1.1,a=this.transform.k,l=Math.max(.1,Math.min(3,a*r));if(l===a)return;const d=l/a;this.transform.x=n-(n-this.transform.x)*d,this.transform.y=o-(o-this.transform.y)*d,this.transform.k=l,this.applyTransform(),this.updateZoomLevel(),this.updateMinimap()},{passive:!1}),this.initMinimapInteraction()}initMinimapInteraction(){const t=this.container.querySelector("#minimap-canvas");if(!t)return;let e=!1;const i=s=>{if(!this.data)return;const n=t.getBoundingClientRect(),o=s.clientX-n.left,r=s.clientY-n.top;this.calculateStats(this.data);const a=this.getTreeBounds();if(!a)return;const l=10,d=Math.min((t.width-l*2)/a.width,(t.height-l*2)/a.height)*.8,c=(o-t.width/2)/d+a.centerX,m=(r-l)/d+a.minY,h=this.container.querySelector("#mlm-canvas").getBoundingClientRect();this.transform.x=h.width/2-c*this.transform.k,this.transform.y=h.height/2-m*this.transform.k,this.applyTransform(),this.updateMinimap()};t.style.cursor="pointer",t.addEventListener("mousedown",s=>{s.preventDefault(),s.stopPropagation(),e=!0,i(s)}),document.addEventListener("mousemove",s=>{e&&(s.preventDefault(),i(s))}),document.addEventListener("mouseup",()=>{e=!1})}getTreeBounds(){const t=this.container.querySelectorAll(".mlm-node");if(t.length===0)return null;let e=1/0,i=-1/0,s=1/0,n=-1/0;return t.forEach(o=>{const a=o.getAttribute("transform").match(/translate\(([^,]+),\s*([^)]+)\)/);if(a){const l=parseFloat(a[1]),d=parseFloat(a[2]);e=Math.min(e,l),i=Math.max(i,l+this.options.nodeWidth),s=Math.min(s,d),n=Math.max(n,d+this.options.nodeHeight)}}),{minX:e,maxX:i,minY:s,maxY:n,width:i-e||1,height:n-s||1,centerX:(e+i)/2,centerY:(s+n)/2}}updateMinimap(){const t=this.container.querySelector("#minimap-canvas"),e=this.container.querySelector("#minimap-viewport");if(!t||!this.data)return;const i=t.getContext("2d"),s=this.getTreeBounds();if(!s)return;t.width=200,t.height=150,i.fillStyle="#f8fafc",i.fillRect(0,0,t.width,t.height);const n=10,o=Math.min((t.width-n*2)/s.width,(t.height-n*2)/s.height)*.8;if(i.fillStyle="#7c3aed",this.container.querySelectorAll(".mlm-node").forEach(a=>{const d=a.getAttribute("transform").match(/translate\(([^,]+),\s*([^)]+)\)/);if(d){const c=parseFloat(d[1])+this.options.nodeWidth/2,m=parseFloat(d[2])+this.options.nodeHeight/2,p=(c-s.centerX)*o+t.width/2,h=(m-s.minY)*o+n;i.beginPath(),i.arc(p,h,3,0,Math.PI*2),i.fill()}}),e){const l=this.container.querySelector("#mlm-canvas").getBoundingClientRect(),d=o/this.transform.k,c=l.width*d,m=l.height*d,p=(-this.transform.x/this.transform.k-s.centerX)*o+t.width/2,h=(-this.transform.y/this.transform.k-s.minY)*o+n;e.style.left=`${Math.max(0,p)}px`,e.style.top=`${Math.max(0,h)}px`,e.style.width=`${Math.min(c,t.width)}px`,e.style.height=`${Math.min(m,t.height)}px`}}applyTransform(){const t=this.container.querySelector("#main-group");t&&t.setAttribute("transform",`translate(${this.transform.x}, ${this.transform.y}) scale(${this.transform.k})`)}zoomIn(){this.transform.k*=1.2,this.transform.k=Math.min(3,this.transform.k),this.applyTransform(),this.updateZoomLevel()}zoomOut(){this.transform.k*=.8,this.transform.k=Math.max(.1,this.transform.k),this.applyTransform(),this.updateZoomLevel()}resetView(){const t=this.container.querySelector("#mlm-canvas");if(t){const e=t.getBoundingClientRect();this.transform={x:e.width/2-200,y:50,k:1}}else this.transform={x:200,y:50,k:1};this.applyTransform(),this.updateZoomLevel(),this.updateMinimap()}updateZoomLevel(){const t=this.container.querySelector("#zoom-level");t&&(t.textContent=Math.round(this.transform.k*100)+"%")}updateStats(){const t=this.calculateStats(this.data);this.container.querySelector("#stat-total-members").textContent=t.totalMembers,this.container.querySelector("#stat-active-members").textContent=t.activeMembers,this.container.querySelector("#stat-total-pv").textContent=t.totalPv.toLocaleString(),this.container.querySelector("#stat-depth").textContent=t.maxDepth}calculateStats(t,e={totalMembers:0,activeMembers:0,totalPv:0,maxDepth:0},i=0){return t&&(e.totalMembers++,t.status==="active"&&e.activeMembers++,e.totalPv+=t.total_pv||0,e.maxDepth=Math.max(e.maxDepth,i),this.options.type==="binary"?(t.left&&this.calculateStats(t.left,e,i+1),t.right&&this.calculateStats(t.right,e,i+1)):(t.children||[]).forEach(s=>this.calculateStats(s,e,i+1))),e}handleSearch(t){const e=this.container.querySelector("#search-results");if(!t){e.classList.remove("show");return}e.innerHTML=`
            <div class="mlm-search-item">
                <strong>MLM001</strong> - John Doe<br>
                <small style="color: #6b7280;">john@example.com</small>
            </div>
        `,e.classList.add("show")}showNodeDetails(t){const e=this.container.querySelector("#node-modal"),i=this.container.querySelector("#modal-body");i.innerHTML=`
            <h2 style="margin: 0 0 20px 0; color: #1f2937;">📋 รายละเอียดสมาชิก</h2>
            <div style="background: #f9fafb; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <div style="font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">
                    ${t.name||"Unknown"}
                </div>
                <div style="color: #6b7280; font-size: 14px;">
                    ${t.member_code} | ${t.email||"No email"}
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
                <div style="background: #ecfdf5; padding: 16px; border-radius: 12px;">
                    <div style="color: #059669; font-size: 12px; font-weight: 600; margin-bottom: 8px;">TOTAL PV</div>
                    <div style="font-size: 24px; font-weight: 700; color: #047857;">${t.total_pv||0}</div>
                </div>
                <div style="background: #ede9fe; padding: 16px; border-radius: 12px;">
                    <div style="color: #7c3aed; font-size: 12px; font-weight: 600; margin-bottom: 8px;">DIRECT REFS</div>
                    <div style="font-size: 24px; font-weight: 700; color: #6d28d9;">${t.direct_referrals||0}</div>
                </div>
            </div>
            <div style="text-align: center;">
                <button onclick="this.closest('.mlm-modal').classList.remove('show')"
                        style="
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
                            color: white;
                            border: none;
                            border-radius: 12px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: transform 0.2s;
                        "
                        onmouseover="this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.transform=''">
                    ✨ ปิด
                </button>
            </div>
        `,e.classList.add("show")}closeModal(){this.container.querySelector("#node-modal").classList.remove("show")}toggleFullscreen(){document.fullscreenElement?document.exitFullscreen():this.container.requestFullscreen()}switchType(t){this.options.type=t,this.loadData().then(()=>this.render())}}window.MlmGenealogyPremium=u;
