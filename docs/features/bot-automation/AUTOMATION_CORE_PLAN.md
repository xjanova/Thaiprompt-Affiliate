# 🤖 ระบบ UI Automation Core - แผนงานการพัฒนา

> **Visual Workflow Builder แบบ n8n สำหรับระบบ Thaiprompt-Affiliate**
>
> Version: 1.0.0 | Created: 2025-11-18 | Status: Planning Phase

---

## 🎯 วิสัยทัศน์และเป้าหมาย

### ภาพรวมโปรเจค

สร้างระบบ **Automation Core** ที่เป็น Visual Workflow Builder แบบ drag-and-drop คล้าย n8n, Zapier, Make.com โดยใช้ **LINE Bot Signup Flow** เป็นโปรเจคต้นแบบแรก

### เป้าหมายหลัก

1. ✅ **Visual Workflow Builder**
   - ลากโหนด (nodes) มาวางบน canvas
   - เชื่อมต่อโหนดด้วยเส้นเชื่อม (connections)
   - กดแก้ไขค่าในแต่ละโหนด
   - ดู flow การทำงานได้ชัดเจน

2. ✅ **LINE Signup Flow Integration**
   - แปลง LINE signup flow ที่มีอยู่เป็น workflow nodes
   - เพิ่ม **MLM Referral Tracking** (ระบุผู้แนะนำ)
   - เพิ่ม **LINE OA Verification** (ต้องเพิ่ม LINE OA ก่อนจึงไปต่อได้)

3. ✅ **เป็นต้นแบบสำหรับ Automation Core ทั้งหมด**
   - ออกแบบให้ขยายได้ (scalable)
   - สามารถเพิ่ม workflow types อื่นๆ ได้ในอนาคต
   - มี standard architecture สำหรับทุก automation

---

## 📊 การวิเคราะห์ LINE Signup Flow ที่มีอยู่

### Current LINE Signup Flow Steps

```
1. Welcome Message (welcome_hero template)
   ↓
2. รับชื่อ (name input)
   ↓
3. รับอีเมล (email input + validation)
   ↓
4. รับเบอร์โทร (phone input + validation)
   ↓
5. OTP Verification (ส่งและยืนยัน OTP)
   ↓
6. รับรหัสผ่าน (password input)
   ↓
7. รหัสผู้แนะนำ (referral code - optional) ⭐ MLM Integration Point
   ↓
8. ยืนยันข้อมูล (confirmation)
   ↓
9. Success + Welcome Message
```

### ⚠️ ปัญหาที่ต้องแก้ไข

1. **ไม่มี MLM Referral Tracking**
   - ไม่บันทึกว่าใครเป็นผู้แนะนำ
   - ไม่เชื่อมโยงกับระบบ MLM members

2. **ไม่มี LINE OA Verification**
   - ไม่มีการบังคับให้ user เพิ่ม LINE OA ที่กำหนด
   - ไม่มีการตรวจสอบว่า user เป็น friend ของ OA แล้วหรือยัง

3. **Flow เป็น Hardcoded**
   - ไม่สามารถแก้ไข flow ผ่าน UI ได้
   - ต้องแก้ code ทุกครั้งที่ต้องการเปลี่ยน flow

---

## 🏗️ สถาปัตยกรรมระบบ Automation Core

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Blade + Alpine.js)             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           Visual Workflow Builder                     │  │
│  │  - Canvas (React Flow / Vue Flow / Custom)            │  │
│  │  - Node Palette (draggable nodes)                     │  │
│  │  - Node Configuration Panels                          │  │
│  │  - Connection Drawing                                 │  │
│  │  - Flow Execution Viewer                              │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↕ API (REST)
┌─────────────────────────────────────────────────────────────┐
│                    Backend (Laravel 11)                     │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           Workflow Engine                             │  │
│  │  - Workflow Definition Storage                        │  │
│  │  - Node Registry                                      │  │
│  │  - Execution Engine                                   │  │
│  │  - State Management                                   │  │
│  │  - Event System                                       │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           Node Types                                  │  │
│  │  - Trigger Nodes (LINE webhook, schedule, etc.)      │  │
│  │  - Action Nodes (send message, save data, etc.)      │  │
│  │  - Condition Nodes (if/else, switch, loop)           │  │
│  │  - Data Nodes (transform, filter, merge)             │  │
│  │  - Integration Nodes (MLM, email, SMS, etc.)         │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    Database (MySQL)                         │
│  - workflows (workflow definitions)                         │
│  - workflow_nodes (node configurations)                     │
│  - workflow_connections (node connections)                  │
│  - workflow_executions (execution history)                  │
│  - workflow_execution_logs (step-by-step logs)              │
│  - workflow_variables (dynamic variables)                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 📋 แผนการพัฒนาแบบละเอียด

### 🔵 Phase 1: Analysis & Design (Week 1)

#### 1.1 วิเคราะห์ LINE Signup Flow ที่มีอยู่ ✅

**Tasks:**
- [x] อ่านและทำความเข้าใจ LINE signup flow
- [x] ระบุ steps ทั้งหมด
- [x] วิเคราะห์ pain points
- [x] ระบุจุดที่ต้องเพิ่ม MLM tracking

**Deliverable:**
- ✅ Current flow diagram
- ✅ Pain points list
- ✅ Integration points

#### 1.2 ออกแบบ Database Schema

**Tables ที่ต้องสร้าง:**

1. **`workflows`** - Workflow definitions
   ```sql
   - id
   - name (ชื่อ workflow)
   - description
   - type (line_signup, email_automation, etc.)
   - status (draft, active, inactive)
   - trigger_type (webhook, schedule, manual)
   - trigger_config (JSON)
   - created_by (user_id)
   - is_template (boolean - เป็น template หรือไม่)
   - version
   - published_at
   - created_at, updated_at, deleted_at
   ```

2. **`workflow_nodes`** - Nodes in workflow
   ```sql
   - id
   - workflow_id
   - node_key (unique per workflow: node_1, node_2, etc.)
   - node_type (trigger, action, condition, data)
   - node_category (line_message, data_validation, mlm, etc.)
   - name (ชื่อ node)
   - description
   - config (JSON - configuration ของ node)
   - position_x (canvas position)
   - position_y (canvas position)
   - order (execution order)
   - is_active
   - created_at, updated_at
   ```

3. **`workflow_connections`** - Connections between nodes
   ```sql
   - id
   - workflow_id
   - source_node_id (จาก node ไหน)
   - source_output (output port: success, fail, etc.)
   - target_node_id (ไป node ไหน)
   - target_input (input port)
   - condition (JSON - เงื่อนไข optional)
   - label (ข้อความบนเส้น)
   - created_at, updated_at
   ```

4. **`workflow_executions`** - Execution history
   ```sql
   - id
   - workflow_id
   - trigger_type (webhook, manual, schedule)
   - trigger_data (JSON - ข้อมูลที่เข้ามา)
   - context (JSON - session data, user data, etc.)
   - status (running, completed, failed, cancelled)
   - started_at
   - completed_at
   - error_message
   - created_at, updated_at
   ```

5. **`workflow_execution_logs`** - Step-by-step logs
   ```sql
   - id
   - execution_id
   - node_id
   - step_number (ลำดับการทำงาน)
   - status (pending, running, completed, failed, skipped)
   - input_data (JSON)
   - output_data (JSON)
   - error_message
   - duration_ms (เวลาที่ใช้ในการทำงาน)
   - started_at
   - completed_at
   - created_at
   ```

6. **`workflow_variables`** - Dynamic variables
   ```sql
   - id
   - workflow_id
   - execution_id (null = global, not null = execution-specific)
   - key (variable name)
   - value (JSON)
   - type (string, number, boolean, object, array)
   - scope (global, execution, session)
   - created_at, updated_at
   ```

7. **`workflow_templates`** - Pre-built workflows
   ```sql
   - id
   - name
   - description
   - category (line_signup, ecommerce, mlm, etc.)
   - workflow_data (JSON - complete workflow definition)
   - thumbnail_url
   - is_official (boolean)
   - usage_count
   - rating
   - created_at, updated_at
   ```

**Relationships:**
- Workflows → WorkflowNodes (one-to-many)
- Workflows → WorkflowConnections (one-to-many)
- Workflows → WorkflowExecutions (one-to-many)
- WorkflowExecutions → WorkflowExecutionLogs (one-to-many)
- WorkflowNodes → WorkflowConnections (source & target)

#### 1.3 ออกแบบ Node Types

**Node Categories:**

1. **🟢 Trigger Nodes** (เริ่มต้น workflow)
   - LINE Webhook Trigger
   - Schedule Trigger (cron)
   - Manual Trigger
   - Webhook Trigger (external)
   - Database Event Trigger

2. **🔵 Action Nodes** (ทำงาน)
   - Send LINE Message
   - Send Flex Message
   - Save to Database
   - Update Database
   - Send Email
   - Send SMS
   - Call External API

3. **🟡 Condition Nodes** (ตัดสินใจ)
   - IF/ELSE
   - Switch (multiple branches)
   - Loop (repeat)
   - Wait/Delay
   - Check Variable

4. **🟣 Data Nodes** (จัดการข้อมูล)
   - Validate Data
   - Transform Data
   - Filter Data
   - Merge Data
   - Extract Data

5. **🟠 Integration Nodes** (เชื่อมต่อระบบอื่น)
   - MLM: Add Member
   - MLM: Set Referrer
   - MLM: Calculate Commission
   - LINE OA: Check Friend Status
   - LINE OA: Add Friend Prompt
   - User: Create Account
   - User: Update Profile

**Node Configuration Schema:**

```json
{
  "nodeType": "send_line_message",
  "config": {
    "messageType": "flex",
    "templateKey": "welcome_hero",
    "variables": {
      "user_name": "{{session.name}}"
    },
    "replyToken": "{{trigger.replyToken}}",
    "errorHandling": {
      "onError": "continue",
      "retryCount": 3,
      "fallbackMessage": "ขออภัย เกิดข้อผิดพลาด"
    }
  },
  "outputs": {
    "success": "next_node",
    "error": "error_handler_node"
  }
}
```

#### 1.4 ออกแบบ UI/UX

**หน้าหลัก: Workflow Builder**

```
┌────────────────────────────────────────────────────────────┐
│  [☰ Menu] [💾 Save] [▶️ Test] [📊 Analytics] [⚙️ Settings] │
├────────────────────────────────────────────────────────────┤
│ 📁 Workflows > LINE Signup Flow                            │
├─────────┬──────────────────────────────────────────────────┤
│  Nodes  │                                                  │
│  📦     │              Canvas Area                         │
│         │                                                  │
│ Trigger │    ┌────────┐                                    │
│  LINE   │    │ Start  │─────→ ┌──────────┐                │
│  Webhook│    └────────┘       │ Welcome  │                │
│         │                     │ Message  │                │
│ Action  │                     └──────────┘                │
│  Send   │                           │                      │
│  LINE   │                           ↓                      │
│  Message│                     ┌──────────┐                │
│         │                     │ Get Name │                │
│  Save   │                     └──────────┘                │
│  Data   │                           │                      │
│         │                           ↓                      │
│ Condition│                    ┌──────────┐                │
│  IF/ELSE│                     │ Validate │                │
│         │                     └──────────┘                │
│ Data    │                      ↙        ↘                 │
│  Validate│             ┌─────────┐  ┌─────────┐           │
│         │             │ Success │  │  Error  │            │
│ Integration│          └─────────┘  └─────────┘            │
│  MLM    │                                                  │
│  LINE OA│                                                  │
└─────────┴──────────────────────────────────────────────────┘
```

**Node Configuration Panel:**

```
┌────────────────────────────────────┐
│  Configure: Send LINE Message      │
├────────────────────────────────────┤
│  Message Type:                     │
│  ○ Text  ● Flex  ○ Image           │
│                                    │
│  Template:                         │
│  [Select Template ▼]               │
│  └─ welcome_hero                   │
│                                    │
│  Variables:                        │
│  user_name: {{session.name}}       │
│  [+ Add Variable]                  │
│                                    │
│  Reply Token:                      │
│  {{trigger.replyToken}}            │
│                                    │
│  Error Handling:                   │
│  On Error: [Continue ▼]            │
│  Retry Count: [3]                  │
│                                    │
│  [Cancel] [Save Configuration]     │
└────────────────────────────────────┘
```

**Deliverables:**
- UI mockups (Figma หรือ hand-drawn)
- Component hierarchy
- User flow diagrams

---

### 🟢 Phase 2: Core Infrastructure (Week 2-3)

#### 2.1 สร้าง Database Tables

**Tasks:**
- [ ] สร้าง migrations สำหรับทุก table
- [ ] สร้าง models พร้อม relationships
- [ ] สร้าง seeders สำหรับ default data
- [ ] ทดสอบ relationships

**Migrations:**
```bash
php artisan make:migration create_workflows_table
php artisan make:migration create_workflow_nodes_table
php artisan make:migration create_workflow_connections_table
php artisan make:migration create_workflow_executions_table
php artisan make:migration create_workflow_execution_logs_table
php artisan make:migration create_workflow_variables_table
php artisan make:migration create_workflow_templates_table
```

**Models:**
- Workflow
- WorkflowNode
- WorkflowConnection
- WorkflowExecution
- WorkflowExecutionLog
- WorkflowVariable
- WorkflowTemplate

#### 2.2 สร้าง Workflow Engine (Core)

**Service Classes:**

1. **`WorkflowEngine`** - หลักการทำงาน
   ```php
   class WorkflowEngine
   {
       public function execute(Workflow $workflow, array $triggerData): WorkflowExecution
       public function resume(WorkflowExecution $execution, string $nodeKey): void
       public function cancel(WorkflowExecution $execution): void
       public function getNextNode(WorkflowNode $currentNode, string $output): ?WorkflowNode
       protected function executeNode(WorkflowNode $node, WorkflowExecution $execution): NodeResult
   }
   ```

2. **`NodeRegistry`** - จัดการ node types
   ```php
   class NodeRegistry
   {
       public function register(string $nodeType, string $handlerClass): void
       public function get(string $nodeType): NodeHandler
       public function all(): array
       public function getByCategory(string $category): array
   }
   ```

3. **`NodeHandler`** (Abstract)
   ```php
   abstract class NodeHandler
   {
       abstract public function execute(WorkflowNode $node, array $context): NodeResult;
       abstract public function validate(array $config): bool;
       abstract public function getConfigSchema(): array;
       public function beforeExecute(WorkflowNode $node, array $context): void {}
       public function afterExecute(NodeResult $result): void {}
       public function onError(\Exception $e): NodeResult {}
   }
   ```

4. **`VariableResolver`** - แทนที่ตัวแปร
   ```php
   class VariableResolver
   {
       public function resolve(string $template, array $context): string
       public function get(string $key, array $context, $default = null)
       public function set(string $key, $value, string $scope = 'execution'): void
   }
   ```

**ตัวอย่าง Node Handlers:**

```php
class SendLineMessageHandler extends NodeHandler
{
    public function execute(WorkflowNode $node, array $context): NodeResult
    {
        $config = $node->config;

        // Resolve variables
        $template = LineSignupTemplate::where('template_key', $config['templateKey'])->first();
        $variables = $this->resolveVariables($config['variables'], $context);

        // Render message
        $flexMessage = $template->render($variables);

        // Send to LINE
        $this->lineMessagingAPI->replyMessage($config['replyToken'], [
            ['type' => 'flex', 'altText' => 'Message', 'contents' => $flexMessage]
        ]);

        return new NodeResult(
            status: 'success',
            output: 'success',
            data: ['messageId' => '...']
        );
    }
}
```

#### 2.3 สร้าง Controllers และ Routes

**Controllers:**
- `WorkflowController` - CRUD workflows
- `WorkflowBuilderController` - Builder UI
- `WorkflowExecutionController` - Execute & monitor
- `WorkflowTemplateController` - Templates

**Routes:**
```php
// Workflow Management
Route::resource('workflows', WorkflowController::class);
Route::get('/workflows/{workflow}/builder', [WorkflowBuilderController::class, 'show']);
Route::post('/workflows/{workflow}/nodes', [WorkflowBuilderController::class, 'addNode']);
Route::put('/workflows/{workflow}/nodes/{node}', [WorkflowBuilderController::class, 'updateNode']);
Route::delete('/workflows/{workflow}/nodes/{node}', [WorkflowBuilderController::class, 'deleteNode']);
Route::post('/workflows/{workflow}/connections', [WorkflowBuilderController::class, 'addConnection']);

// Execution
Route::post('/workflows/{workflow}/execute', [WorkflowExecutionController::class, 'execute']);
Route::get('/workflows/{workflow}/executions', [WorkflowExecutionController::class, 'index']);
Route::get('/executions/{execution}', [WorkflowExecutionController::class, 'show']);
Route::post('/executions/{execution}/cancel', [WorkflowExecutionController::class, 'cancel']);
```

---

### 🟡 Phase 3: Visual Workflow Builder (Week 4-5)

#### 3.1 เลือก Technology Stack

**Options:**

**Option A: React Flow (แนะนำ) ⭐**
- Library: React Flow (https://reactflow.dev/)
- Framework: React (ฝัง Laravel Blade)
- Pros: มี features ครบ, community ใหญ่, customize ง่าย
- Cons: ต้องเรียนรู้ React (แต่คุ้มค่า)

**Option B: Vue Flow**
- Library: Vue Flow
- Framework: Vue 3
- Pros: ใกล้เคียง Alpine.js, syntax คล้ายกัน
- Cons: community เล็กกว่า React Flow

**Option C: Custom with D3.js + Alpine.js**
- Library: D3.js สำหรับ visualization
- Framework: Alpine.js (V3 standard)
- Pros: ตรงตาม V3 guidelines 100%
- Cons: ต้องเขียน flow logic เอง, ใช้เวลานาน

**🎯 คำแนะนำ: ใช้ React Flow**
- เร็วที่สุด
- Features ครบที่สุด
- มี examples และ documentation เยอะ
- สามารถฝังใน Laravel Blade ได้

#### 3.2 สร้าง Components

**React Components:**

```javascript
// Main Components
<WorkflowBuilder />
  ├─ <Canvas />              // React Flow canvas
  ├─ <NodePalette />         // Draggable nodes sidebar
  ├─ <NodeConfigPanel />     // Configuration panel
  ├─ <Toolbar />             // Top toolbar
  └─ <MiniMap />             // Overview map

// Custom Nodes
<TriggerNode />
<ActionNode />
<ConditionNode />
<DataNode />
<IntegrationNode />
```

**Canvas Component:**
```jsx
import ReactFlow, {
  Background,
  Controls,
  MiniMap
} from 'reactflow';

function Canvas({ nodes, edges, onNodesChange, onEdgesChange, onConnect }) {
  return (
    <ReactFlow
      nodes={nodes}
      edges={edges}
      onNodesChange={onNodesChange}
      onEdgesChange={onEdgesChange}
      onConnect={onConnect}
      fitView
    >
      <Background />
      <Controls />
      <MiniMap />
    </ReactFlow>
  );
}
```

**Node Palette Component:**
```jsx
function NodePalette({ categories }) {
  const onDragStart = (event, nodeType) => {
    event.dataTransfer.setData('application/reactflow', nodeType);
    event.dataTransfer.effectAllowed = 'move';
  };

  return (
    <div className="node-palette">
      {categories.map(category => (
        <div key={category.name} className="category">
          <h3>{category.name}</h3>
          {category.nodes.map(node => (
            <div
              key={node.type}
              className="node-item"
              onDragStart={(e) => onDragStart(e, node.type)}
              draggable
            >
              <i className={node.icon}></i>
              <span>{node.label}</span>
            </div>
          ))}
        </div>
      ))}
    </div>
  );
}
```

#### 3.3 สร้าง Backend API สำหรับ Builder

**API Endpoints:**

```php
// Get workflow definition
GET /api/workflows/{workflow}/definition
Response: {
  workflow: {...},
  nodes: [...],
  connections: [...],
  variables: [...]
}

// Save workflow
PUT /api/workflows/{workflow}/definition
Request: {
  nodes: [...],
  connections: [...],
  variables: [...]
}

// Get node types
GET /api/workflow/node-types
Response: {
  categories: [
    {
      name: "Triggers",
      nodes: [
        {
          type: "line_webhook_trigger",
          label: "LINE Webhook",
          icon: "fab fa-line",
          configSchema: {...}
        }
      ]
    }
  ]
}
```

---

### 🔴 Phase 4: LINE Signup Flow Integration (Week 6)

#### 4.1 แปลง LINE Signup Flow เป็น Workflow

**สร้าง Workflow Template: "LINE Signup Flow"**

**Nodes ใน Flow:**

1. **Trigger: LINE Webhook** (node_1)
   - Type: `line_webhook_trigger`
   - Config: Listen to message events

2. **Action: Send Welcome Message** (node_2)
   - Type: `send_flex_message`
   - Config: Template = `welcome_hero`

3. **Action: Ask for Name** (node_3)
   - Type: `ask_input`
   - Config: Input type = text, Save to = `session.name`

4. **Action: Ask for Email** (node_4)
   - Type: `ask_input`
   - Config: Input type = email, Validation = email format

5. **Condition: Validate Email** (node_5)
   - Type: `validate_data`
   - Config: Field = `session.email`, Rules = email, unique

6. **Action: Ask for Phone** (node_6)
   - Type: `ask_input`
   - Config: Input type = tel

7. **Action: Send OTP** (node_7)
   - Type: `send_otp`
   - Config: To = `session.phone`

8. **Action: Verify OTP** (node_8)
   - Type: `verify_otp`

9. **⭐ NEW: LINE OA Verification** (node_9)
   - Type: `check_line_friend_status`
   - Config: OA ID = `@yourlineoa`
   - Outputs:
     - `is_friend` → Continue
     - `not_friend` → Prompt to add OA

10. **⭐ NEW: Prompt Add LINE OA** (node_10)
    - Type: `prompt_add_line_oa`
    - Config: OA Link, Message
    - Wait until user adds OA

11. **Action: Ask for Password** (node_11)
    - Type: `ask_input`
    - Config: Input type = password, min length = 8

12. **⭐ NEW: Ask for Referral Code** (node_12)
    - Type: `ask_referral_code`
    - Config: Optional = true

13. **⭐ NEW: Validate Referral Code** (node_13)
    - Type: `validate_mlm_referral`
    - Config: Check if code exists in `mlm_members`

14. **Action: Create User Account** (node_14)
    - Type: `create_user`
    - Config: Map session data to user fields

15. **⭐ NEW: Add to MLM System** (node_15)
    - Type: `mlm_add_member`
    - Config:
      - User ID = `{{created_user.id}}`
      - Referrer = `{{validated_referral.member_id}}`
      - Position = auto

16. **Action: Send Success Message** (node_16)
    - Type: `send_flex_message`
    - Config: Template = `quick_start_guide`

17. **Action: Send Welcome Email** (node_17)
    - Type: `send_email`
    - Config: Template = welcome, To = `{{session.email}}`

**Connections:**
```
node_1 → node_2 → node_3 → node_4 → node_5
                                      ↓ valid ↓ invalid
                                   node_6     node_4 (retry)
                                      ↓
                                   node_7 → node_8
                                              ↓ valid ↓ invalid
                                           node_9     node_7 (retry)
                                              ↓ is_friend ↓ not_friend
                                           node_11        node_10 → node_9 (check again)
                                              ↓
                                           node_12 → node_13
                                                      ↓ valid ↓ skip
                                                   node_14   node_14
                                                      ↓
                                                   node_15 → node_16 → node_17
```

#### 4.2 สร้าง MLM Integration Nodes

**Node Handler: MLMAddMemberHandler**

```php
class MLMAddMemberHandler extends NodeHandler
{
    public function execute(WorkflowNode $node, array $context): NodeResult
    {
        $config = $node->config;

        // Get user and referrer
        $userId = $this->variableResolver->resolve($config['userId'], $context);
        $referrerId = $this->variableResolver->resolve($config['referrerId'], $context);

        // Create MLM member
        $mlmMember = MlmMember::create([
            'user_id' => $userId,
            'sponsor_id' => $referrerId,
            'member_code' => $this->generateMemberCode(),
            'join_date' => now(),
            'status' => 'active',
        ]);

        // Build genealogy tree
        $this->mlmTreeBuilder->addToTree($mlmMember, $referrerId);

        return new NodeResult(
            status: 'success',
            output: 'success',
            data: [
                'mlm_member_id' => $mlmMember->id,
                'member_code' => $mlmMember->member_code,
            ]
        );
    }
}
```

**Node Handler: CheckLineFriendStatusHandler**

```php
class CheckLineFriendStatusHandler extends NodeHandler
{
    public function execute(WorkflowNode $node, array $context): NodeResult
    {
        $config = $node->config;
        $lineUserId = $context['trigger']['source']['userId'];

        // Call LINE API to check friend status
        $isFriend = $this->lineMessagingAPI->getFriendship($lineUserId);

        return new NodeResult(
            status: 'success',
            output: $isFriend ? 'is_friend' : 'not_friend',
            data: ['is_friend' => $isFriend]
        );
    }
}
```

**Node Handler: PromptAddLineOAHandler**

```php
class PromptAddLineOAHandler extends NodeHandler
{
    public function execute(WorkflowNode $node, array $context): NodeResult
    {
        $config = $node->config;
        $replyToken = $context['trigger']['replyToken'];

        // Send message with button to add OA
        $flexMessage = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => 'กรุณาเพิ่ม LINE OA เพื่อดำเนินการต่อ',
                        'weight' => 'bold',
                    ]
                ]
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'uri',
                            'label' => 'เพิ่ม LINE OA',
                            'uri' => $config['oaLink'],
                        ],
                        'style' => 'primary',
                    ]
                ]
            ]
        ];

        $this->lineMessagingAPI->replyMessage($replyToken, [
            ['type' => 'flex', 'altText' => 'เพิ่ม LINE OA', 'contents' => $flexMessage]
        ]);

        // Pause execution - wait for user to add OA
        return new NodeResult(
            status: 'waiting',
            output: 'waiting_for_user',
            data: ['wait_for' => 'line_oa_add']
        );
    }
}
```

#### 4.3 สร้าง Workflow Template Seeder

```php
class LineSignupWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // สร้าง workflow
        $workflow = Workflow::create([
            'name' => 'LINE Signup Flow with MLM',
            'description' => 'ระบบสมัครสมาชิกผ่าน LINE พร้อม MLM referral tracking',
            'type' => 'line_signup',
            'trigger_type' => 'webhook',
            'status' => 'active',
            'is_template' => true,
        ]);

        // สร้าง nodes (17 nodes)
        $nodes = [
            ['node_key' => 'trigger_1', 'node_type' => 'trigger', 'node_category' => 'line_webhook', ...],
            ['node_key' => 'action_welcome', 'node_type' => 'action', 'node_category' => 'send_message', ...],
            // ... เพิ่ม nodes ทั้งหมด
        ];

        foreach ($nodes as $nodeData) {
            WorkflowNode::create(array_merge(['workflow_id' => $workflow->id], $nodeData));
        }

        // สร้าง connections
        $connections = [
            ['source_node_key' => 'trigger_1', 'target_node_key' => 'action_welcome', 'source_output' => 'success'],
            // ... เพิ่ม connections ทั้งหมด
        ];

        foreach ($connections as $connData) {
            WorkflowConnection::create([...]);
        }
    }
}
```

---

### 🟣 Phase 5: Testing & Documentation (Week 7)

#### 5.1 Testing

**Unit Tests:**
- [ ] Test WorkflowEngine
- [ ] Test each NodeHandler
- [ ] Test VariableResolver
- [ ] Test NodeRegistry

**Integration Tests:**
- [ ] Test complete LINE signup flow
- [ ] Test MLM referral tracking
- [ ] Test LINE OA verification
- [ ] Test error handling

**E2E Tests:**
- [ ] Test workflow builder UI
- [ ] Test drag-and-drop nodes
- [ ] Test node configuration
- [ ] Test workflow execution

#### 5.2 Documentation

**Developer Documentation:**
- [ ] API documentation
- [ ] Node development guide
- [ ] Workflow engine architecture
- [ ] Database schema

**User Documentation:**
- [ ] Workflow builder user guide
- [ ] Node reference (แต่ละ node ใช้ยังไง)
- [ ] LINE signup flow tutorial
- [ ] MLM integration guide

---

## 🚀 Deployment Plan

### Week 8: Production Deployment

1. **Database Migration**
   ```bash
   php artisan migrate
   php artisan db:seed --class=WorkflowSystemSeeder
   php artisan db:seed --class=LineSignupWorkflowSeeder
   ```

2. **Frontend Build**
   ```bash
   npm run build
   ```

3. **Configure LINE Webhook**
   - อัพเดท webhook URL ให้ชี้ไปที่ workflow execution endpoint
   - ทดสอบการรับ events

4. **Monitor & Optimize**
   - ตั้ง logging
   - ตั้ง monitoring (execution time, error rate)
   - Optimize performance

---

## 📊 Success Metrics

### KPIs

1. **Technical Metrics:**
   - Workflow execution success rate > 95%
   - Average execution time < 3 seconds
   - Node configuration error rate < 5%
   - UI responsiveness (60 FPS)

2. **User Metrics:**
   - LINE signup completion rate > 80%
   - MLM referral tracking accuracy = 100%
   - LINE OA verification success rate > 90%

3. **Business Metrics:**
   - Number of workflows created
   - Number of successful signups via workflow
   - MLM tree growth rate
   - User satisfaction score

---

## 🔮 Future Enhancements

### Phase 6+: Advanced Features

1. **More Node Types:**
   - Payment nodes (Stripe, PayPal, PromptPay)
   - SMS nodes (Twilio, Thai SMS providers)
   - Push notification nodes
   - Webhook nodes (call external APIs)
   - Database query nodes

2. **Advanced Workflow Features:**
   - Version control (workflow versions)
   - A/B testing (test different flows)
   - Workflow analytics (conversion funnels)
   - Workflow marketplace (share/sell workflows)

3. **AI/ML Integration:**
   - AI-suggested next nodes
   - Auto-optimize workflows
   - Anomaly detection
   - Predictive analytics

4. **Multi-Channel Support:**
   - Facebook Messenger
   - WhatsApp
   - Telegram
   - Email automation
   - SMS automation

---

## ⚠️ Risks & Mitigations

### Technical Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| React Flow ไม่รองรับ use case | High | Low | ทดสอบ POC ก่อน, มี fallback เป็น Vue Flow |
| Performance issues with large workflows | Medium | Medium | Implement pagination, lazy loading |
| Database performance with many executions | Medium | High | Add indexes, archive old data |
| LINE API rate limits | High | Medium | Implement queue system, retry logic |

### Business Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Users ไม่เข้าใจการใช้งาน | High | Medium | Comprehensive documentation, tutorials |
| Migration จาก old flow ยาก | Medium | Low | Provide migration tool, support |
| MLM tracking ผิดพลาด | Critical | Low | Extensive testing, audit logs |

---

## 📅 Timeline Summary

| Phase | Duration | Start | End | Deliverables |
|-------|----------|-------|-----|--------------|
| Phase 1: Design | 1 week | Week 1 | Week 1 | DB schema, UI mockups, node types |
| Phase 2: Backend | 2 weeks | Week 2 | Week 3 | Workflow engine, models, APIs |
| Phase 3: Frontend | 2 weeks | Week 4 | Week 5 | Visual builder UI |
| Phase 4: Integration | 1 week | Week 6 | Week 6 | LINE flow with MLM |
| Phase 5: Testing | 1 week | Week 7 | Week 7 | Tests, documentation |
| Phase 6: Deployment | 1 week | Week 8 | Week 8 | Production launch |

**Total Duration: 8 weeks (2 months)**

---

## 💰 Resource Requirements

### Development Team

- **Backend Developer** (Laravel): 1 person, full-time
- **Frontend Developer** (React): 1 person, full-time
- **UI/UX Designer**: 1 person, part-time (Week 1)
- **QA Tester**: 1 person, part-time (Week 7-8)

### Infrastructure

- **Development Server**: 1x VPS (4GB RAM, 2 CPU)
- **Staging Server**: 1x VPS (8GB RAM, 4 CPU)
- **Production Server**: 1x VPS (16GB RAM, 8 CPU)
- **Database**: MySQL 8.0+
- **Redis**: For queue and caching

### Tools & Services

- **LINE Messaging API**: Official Account (Free or Premium)
- **Development Tools**: VS Code, Git, Postman
- **Design Tools**: Figma (for UI mockups)
- **Monitoring**: Laravel Telescope, Sentry

---

## ✅ Go/No-Go Decision Criteria

### ก่อนเริ่ม Phase 2 (Backend Development)

- [ ] Database schema ได้รับ approval
- [ ] UI mockups ได้รับ approval
- [ ] Node types ครบถ้วนสำหรับ LINE signup flow
- [ ] Technical stack ถูกเลือกและ approved

### ก่อนเริ่ม Phase 4 (Integration)

- [ ] Workflow engine ทำงานได้
- [ ] Visual builder ใช้งานได้
- [ ] ผ่าน unit tests อย่างน้อย 80%

### ก่อน Production Deployment

- [ ] ผ่าน integration tests 100%
- [ ] ผ่าน E2E tests
- [ ] Documentation ครบถ้วน
- [ ] Performance benchmarks ผ่าน
- [ ] Security audit ผ่าน

---

## 📚 References

### Technical Documentation

- **React Flow**: https://reactflow.dev/
- **Laravel Workflow Package**: https://github.com/biigle/laravel-workflow
- **n8n Source Code**: https://github.com/n8n-io/n8n (for inspiration)
- **LINE Messaging API**: https://developers.line.biz/en/docs/messaging-api/

### Design Inspiration

- **n8n**: https://n8n.io
- **Zapier**: https://zapier.com
- **Make.com**: https://www.make.com
- **Pipedream**: https://pipedream.com

---

**Document Version**: 1.0.0
**Created**: 2025-11-18
**Status**: ✅ Planning Phase Complete - Ready for Review
**Next Step**: Review & Approval → Start Phase 2

---

## 🤔 คำถามสำหรับ Review

1. **Database Schema ครบถ้วนหรือยัง?** มี table อะไรที่ขาดไปหรือไม่?
2. **Node Types เพียงพอหรือไม่?** ต้องเพิ่ม node type อะไรอีกสำหรับ LINE signup flow?
3. **React Flow เหมาะสมหรือไม่?** หรือควรใช้ Vue Flow / Custom?
4. **MLM Integration ครบถ้วนหรือยัง?** ต้องเพิ่มอะไรอีกสำหรับ MLM system?
5. **Timeline สมเหตุสมผลหรือไม่?** 8 สัปดาห์เพียงพอหรือต้องปรับ?

**พร้อมเริ่มพัฒนาหรือยัง?** 🚀
