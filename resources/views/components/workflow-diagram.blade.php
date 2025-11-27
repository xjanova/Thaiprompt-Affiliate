{{--
    Workflow Diagram Component
    ระบบวาดผังสายงานแบบ Interactive เหมือน n8n

    ฟีเจอร์:
    - ลาก nodes ได้อิสระ (Free dragging)
    - ลากเส้นเชื่อมต่อ (Connection drawing)
    - Bezier curves แบบ n8n
    - Snap to grid
    - Zoom & Pan
    - Multi-select
    - Touch support

    การใช้งาน:
    <x-workflow-diagram
        id="my-diagram"
        :editable="true"
        :snap-to-grid="true"
        :data="$treeData"
    />

    หรือเรียกใช้ผ่าน JavaScript:
    const diagram = new WorkflowDiagram('my-diagram', options);
    diagram.loadTreeData(treeData);

    @author TP-Affiliate Team
    @version 1.0.0
--}}

@props([
    'id' => 'workflow-diagram-' . uniqid(),
    'height' => '600px',
    'minHeight' => '500px',
    'editable' => true,
    'snapToGrid' => true,
    'showGrid' => true,
    'gridSize' => 20,
    'nodeWidth' => 200,
    'nodeHeight' => 80,
    'animateConnections' => true,
    'data' => null,
    'treeData' => null,
    'apiUrl' => null,
    'treeType' => 'unilevel',
    'maxDepth' => 5,
])

<div
    {{ $attributes->merge(['class' => 'workflow-diagram-wrapper rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-xl']) }}
    style="height: {{ $height }}; min-height: {{ $minHeight }};"
>
    <div id="{{ $id }}" class="w-full h-full"></div>
</div>

@once
@push('scripts')
<script src="{{ asset('assets/js/workflow-diagram.js') }}"></script>
@endpush
@endonce

@push('scripts')
<script>
(function() {
    /**
     * Initialize Workflow Diagram
     */
    function initWorkflowDiagram() {
        const containerId = '{{ $id }}';
        const container = document.getElementById(containerId);

        if (!container) {
            console.error('WorkflowDiagram: Container not found:', containerId);
            return;
        }

        // ตรวจสอบว่า class พร้อมใช้งานหรือไม่
        if (typeof WorkflowDiagram === 'undefined') {
            console.error('WorkflowDiagram class not loaded. Make sure workflow-diagram.js is included.');
            return;
        }

        // สร้าง instance
        const diagram = new WorkflowDiagram(container, {
            editable: {{ $editable ? 'true' : 'false' }},
            snapToGrid: {{ $snapToGrid ? 'true' : 'false' }},
            showGrid: {{ $showGrid ? 'true' : 'false' }},
            gridSize: {{ $gridSize }},
            nodeWidth: {{ $nodeWidth }},
            nodeHeight: {{ $nodeHeight }},
            animateConnections: {{ $animateConnections ? 'true' : 'false' }},

            // Event callbacks
            onNodeClick: function(node) {
                // Emit custom event ให้ parent รับได้
                container.dispatchEvent(new CustomEvent('node-click', {
                    detail: node,
                    bubbles: true
                }));
            },
            onNodeDragEnd: function(node) {
                container.dispatchEvent(new CustomEvent('node-drag-end', {
                    detail: node,
                    bubbles: true
                }));
            },
            onConnectionCreate: function(connection) {
                container.dispatchEvent(new CustomEvent('connection-create', {
                    detail: connection,
                    bubbles: true
                }));
            },
            onConnectionDelete: function(connection) {
                container.dispatchEvent(new CustomEvent('connection-delete', {
                    detail: connection,
                    bubbles: true
                }));
            },
            onSelectionChange: function(selectedIds) {
                container.dispatchEvent(new CustomEvent('selection-change', {
                    detail: selectedIds,
                    bubbles: true
                }));
            }
        });

        // เก็บ instance ไว้ใน element
        container._workflowDiagram = diagram;

        // โหลดข้อมูลถ้ามี
        @if($data)
        diagram.setData(@json($data));
        @elseif($treeData)
        diagram.loadTreeData(@json($treeData), {
            horizontalSpacing: 280,
            verticalSpacing: 150
        });
        @elseif($apiUrl)
        // Fetch จาก API
        fetch('{{ $apiUrl }}')
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    diagram.loadTreeData(result.data, {
                        horizontalSpacing: 280,
                        verticalSpacing: 150
                    });
                } else {
                    console.error('Failed to load data:', result.message);
                }
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
        @endif

        console.log('WorkflowDiagram initialized:', containerId);
    }

    // Initialize เมื่อ DOM พร้อม
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWorkflowDiagram);
    } else {
        // DOM พร้อมแล้ว
        setTimeout(initWorkflowDiagram, 100);
    }
})();
</script>
@endpush
