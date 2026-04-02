<template>
  <Card title="Risks" :hide-title-on-mobile="true">
    <template #actions>
      <div class="flex items-center gap-2">
        <Button variant="secondary" size="sm" @click="showImport = true">
          <svg
            class="w-4 h-4 mr-1 inline"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
            />
          </svg>
          Import
        </Button>
        <Button variant="primary" size="sm" @click="openModal()"
          >+ New Risk</Button
        >
      </div>
    </template>

    <DataTable
      :columns="[
        { key: 'code', label: 'Code' },
        { key: 'type', label: 'Type', align: 'center' },
        { key: 'project', label: 'Project' },
        { key: 'category', label: 'Category' },
        { key: 'description', label: 'Description' },
        { key: 'l', label: 'L', align: 'center' },
        { key: 'i', label: 'I', align: 'center' },
        { key: 's', label: 'S', align: 'center' },
        { key: 'level', label: 'Level', align: 'center' },
        { key: 'owner', label: 'Owner' },
        { key: 'status', label: 'Status', align: 'center' },
        { key: 'actions', label: 'Actions', align: 'center' },
      ]"
      :rows="risks.data"
      :pagination="risks.links"
      empty-message="No risks yet."
      v-slot="{ row: r }"
    >
      <td class="py-2 px-3 font-mono text-xs text-gray-500">
        {{ r.risk_code }}
      </td>
      <td class="text-center py-2 px-3">
        <span
          class="text-xs px-1.5 py-0.5 rounded"
          :class="
            r.record_type === 'Issue'
              ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
              : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
          "
          >{{ r.record_type || "Risk" }}</span
        >
      </td>
      <td class="py-2 px-3 text-gray-700 dark:text-gray-200">
        {{ r.project?.project_code || "—" }}
      </td>
      <td class="py-2 px-3 text-gray-500">{{ r.risk_category }}</td>
      <td
        class="py-2 px-3 text-gray-900 dark:text-white max-w-sm truncate"
        :title="r.risk_description"
      >
        {{ r.risk_description }}
      </td>
      <td class="text-center py-2 px-3">{{ r.likelihood }}</td>
      <td class="text-center py-2 px-3">{{ r.impact }}</td>
      <td class="text-center py-2 px-3 font-bold">{{ r.severity }}</td>
      <td class="text-center py-2 px-3">
        <Badge
          variant="dot"
          :color="getRagColor(r.risk_level)"
          :label="r.risk_level"
        />
      </td>
      <td class="py-2 px-3 text-gray-500">{{ r.owner || "—" }}</td>
      <td class="text-center py-2 px-3">
        <Badge
          variant="dot"
          :color="getRiskStatusColor(r.status)"
          :label="r.status"
        />
      </td>
      <td class="text-center py-2 px-3">
        <button
          @click="editEntry(r)"
          class="text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-white text-xs mr-2 transition-colors"
        >
          Edit
        </button>
        <button
          @click="deleteEntry(r.id)"
          class="text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 text-xs transition-colors"
        >
          Delete
        </button>
      </td>
    </DataTable>
  </Card>

  <Modal
    :show="showModal"
    :title="editingId ? 'Edit Risk' : 'New Risk'"
    max-width="2xl"
    @close="closeModal"
  >
    <form @submit.prevent="submitEntry" class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <Input
          v-model="form.risk_code"
          label="Risk Code"
          placeholder="e.g. R-001"
          required
          :error="form.errors.risk_code"
        />
        <Select
          v-model="form.record_type"
          :options="recordTypeOpts"
          label="Type"
          required
          :error="form.errors.record_type"
        />
      </div>
      <Select
        v-model="form.pp_project_id"
        :options="projectOptions"
        label="Project (optional)"
        :error="form.errors.pp_project_id"
      />
      <Input
        v-model="form.risk_category"
        label="Category"
        placeholder="e.g. Wayleave/Compensation"
        required
        :error="form.errors.risk_category"
      />
      <Textarea
        v-model="form.risk_description"
        label="Risk Description"
        :rows="3"
        required
        placeholder="Describe the risk..."
        :error="form.errors.risk_description"
      />
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <Input
          v-model="form.likelihood"
          type="number"
          min="1"
          max="5"
          step="1"
          label="Likelihood (1-5)"
          required
          :error="form.errors.likelihood"
        />
        <Input
          v-model="form.impact"
          type="number"
          min="1"
          max="5"
          step="1"
          label="Impact (1-5)"
          required
          :error="form.errors.impact"
        />
        <Select
          v-model="form.risk_level"
          :options="ragOptions"
          label="Risk Level"
          required
          :error="form.errors.risk_level"
        />
      </div>
      <Textarea
        v-model="form.mitigation"
        label="Mitigation"
        :rows="2"
        placeholder="Mitigation plan..."
      />
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <Input v-model="form.owner" label="Owner" :error="form.errors.owner" />
        <DatePicker
          v-model="form.due_date"
          label="Due Date"
          size="md"
          :error="form.errors.due_date"
        />
        <Select
          v-model="form.status"
          :options="statusOpts"
          label="Status"
          required
          :error="form.errors.status"
        />
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <DatePicker
          v-model="form.created_date"
          label="Created Date"
          size="md"
          :error="form.errors.created_date"
        />
        <Input
          v-model="form.days_open"
          type="number"
          step="1"
          min="0"
          label="Days Open"
          :error="form.errors.days_open"
        />
      </div>
      <div class="flex items-center gap-3 pt-2">
        <Button
          type="submit"
          variant="primary"
          size="md"
          :disabled="form.processing"
          class="flex-1"
        >
          {{ form.processing ? "Saving..." : editingId ? "Update" : "Create" }}
        </Button>
        <Button
          type="button"
          variant="secondary"
          size="md"
          @click="closeModal"
          class="flex-1"
          >Cancel</Button
        >
      </div>
    </form>
  </Modal>

  <!-- Import Modal -->
  <PpImportModal
    :show="showImport"
    entity="risks"
    @close="showImport = false"
    @imported="
      () => {
        showImport = false;
        router.reload();
      }
    "
  />
</template>

<script setup>
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import Card from "@/Components/UI/Card.vue";
import DataTable from "@/Components/UI/DataTable.vue";
import Input from "@/Components/UI/Input.vue";
import DatePicker from "@/Components/UI/DatePicker.vue";
import Select from "@/Components/UI/Select.vue";
import Button from "@/Components/UI/Button.vue";
import Textarea from "@/Components/UI/Textarea.vue";
import Modal from "@/Components/UI/Modal.vue";
import Badge from "@/Components/UI/Badge.vue";
import PpImportModal from "@/Components/PpImportModal.vue";
import { useBadges } from "@/Composables/useBadges";

const { getRiskStatusColor, getRagColor } = useBadges();

const props = defineProps({
  risks: { type: Object, default: () => ({ data: [], links: [] }) },
  ppProjects: { type: Array, default: () => [] },
});

const showModal = ref(false);
const showImport = ref(false);
const editingId = ref(null);

const projectOptions = computed(() => [
  { value: "", label: "— Portfolio-wide —" },
  ...props.ppProjects.map((p) => ({
    value: p.id,
    label: `${p.project_code} — ${p.project_name}`,
  })),
]);
const ragOptions = [
  { value: "Red", label: "Red" },
  { value: "Amber", label: "Amber" },
  { value: "Green", label: "Green" },
];
const statusOpts = [
  { value: "Open", label: "Open" },
  { value: "Mitigating", label: "Mitigating" },
  { value: "Closed", label: "Closed" },
  { value: "In Progress", label: "In Progress" },
];
const recordTypeOpts = [
  { value: "Risk", label: "Risk" },
  { value: "Issue", label: "Issue" },
];

const form = useForm({
  risk_code: "",
  pp_project_id: "",
  risk_category: "",
  risk_description: "",
  likelihood: 3,
  impact: 3,
  risk_level: "Amber",
  mitigation: "",
  owner: "",
  due_date: "",
  status: "Open",
  notes: "",
  record_type: "Risk",
  created_date: "",
  days_open: null,
});

function openModal() {
  resetForm();
  showModal.value = true;
}
function closeModal() {
  showModal.value = false;
  resetForm();
}

function submitEntry() {
  if (editingId.value) {
    form.put(`/pp/risks/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post("/pp/risks", {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
}

function editEntry(r) {
  editingId.value = r.id;
  Object.keys(form.data()).forEach((k) => {
    if (k in r) form[k] = r[k] ?? "";
  });
  form.pp_project_id = r.pp_project_id || "";
  showModal.value = true;
}

function resetForm() {
  editingId.value = null;
  form.reset();
  form.likelihood = 3;
  form.impact = 3;
  form.risk_level = "Amber";
  form.status = "Open";
  form.record_type = "Risk";
}

function deleteEntry(id) {
  if (confirm("Delete this risk?")) {
    router.delete(`/pp/risks/${id}`, { preserveScroll: true });
  }
}
</script>
