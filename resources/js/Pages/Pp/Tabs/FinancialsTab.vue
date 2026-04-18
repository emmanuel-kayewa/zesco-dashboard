<template>
  <Card title="Financials" :hide-title-on-mobile="true">
    <template #actions>
      <div class="flex flex-wrap items-center gap-2">
        <!-- Mobile: Filter button with dropdown -->
        <div class="relative md:hidden filter-dropdown-container">
          <Button
            variant="secondary"
            size="sm"
            @click="showFilters = !showFilters"
          >
            <span class="flex items-center">
              <svg
                class="w-4 h-4 mr-1.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                />
              </svg>
              Filters
            </span>
          </Button>
          <div
            v-if="showFilters"
            class="absolute left-0 top-full mt-1 w-56 sm:w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3 space-y-3 z-50"
          >
            <Input
              v-model="searchQuery"
              icon="search"
              placeholder="Search financials…"
              title="Search by finance ID, contract ID, project code, or project name"
              size="sm"
              class="w-full"
            />
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
              <Select
                v-model="currencyFilter"
                :options="[
                  { value: 'USD', label: 'USD' },
                  { value: 'ZMW', label: 'ZMW' },
                ]"
                placeholder="All Currencies"
                size="sm"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Project</label>
              <Select
                v-model="projectFilter"
                :options="projectFilterOptions"
                placeholder="All Projects"
                size="sm"
                searchable
                class="w-full"
              />
            </div>
            <Button
              variant="secondary"
              size="sm"
              @click="showFilters = false"
              class="w-full"
            >Close</Button>
          </div>
        </div>

        <!-- Desktop: Search + Inline filters -->
        <Input
          v-model="searchQuery"
          icon="search"
          placeholder="Search financials…"
          title="Search by finance ID, contract ID, project code, or project name"
          size="md"
          class="w-48 hidden md:block"
        />
        <Select
          v-model="currencyFilter"
          :options="[
            { value: 'USD', label: 'USD' },
            { value: 'ZMW', label: 'ZMW' },
          ]"
          placeholder="All Currencies"
          size="md"
          class="w-20 sm:w-32 hidden md:block"
        />
        <Select
          v-model="projectFilter"
          :options="projectFilterOptions"
          placeholder="All Projects"
          size="md"
          searchable
          class="w-44 hidden md:block"
        />
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
        <Button variant="primary" size="sm" @click="openModal()">
          <span class="hidden md:block">+ New Record</span>
          <span class="block md:hidden">+ New</span>
        </Button>
      </div>
    </template>

    <DataTable
      :columns="[
        { key: 'code', label: 'Finance ID', class: 'text-nowrap' },
        { key: 'contract', label: 'Contract ID', class: 'text-nowrap' },
        { key: 'project', label: 'Project' },
        { key: 'date', label: 'As-Of Date' },
        { key: 'committed', label: 'Committed', align: 'right' },
        {
          key: 'paid',
          label: 'Paid-to-Date',
          align: 'right',
          class: 'text-nowrap',
        },
        { key: 'currency', label: 'Currency', align: 'center' },
        { key: 'notes', label: 'Notes' },
        { key: 'actions', label: 'Actions', align: 'center' },
      ]"
      :rows="financials.data"
      :pagination="financials.links"
      empty-message="No financial records yet."
    >
      <template #default="{ row: f }">
        <td class="py-2 px-3 font-mono text-xs text-gray-500">
          {{ f.finance_code }}
        </td>
        <td class="py-2 px-3 text-gray-500">
          {{ f.contract_id || "—" }}
        </td>
        <td class="py-2 px-3 text-gray-700 dark:text-gray-200">
          <template v-if="f.project">
            <span class="font-mono text-xs text-gray-500">{{ f.project.project_code }}</span>
            <span class="block text-sm">{{ f.project.project_name }}</span>
          </template>
          <span v-else>—</span>
        </td>
        <td class="py-2 px-3 text-gray-500">{{ f.as_of_date }}</td>
        <td
          class="text-right py-2 px-3 text-gray-700 dark:text-gray-200 font-semibold"
        >
          {{ formatNum(f.committed_amount) }}
        </td>
        <td
          class="text-right py-2 px-3 text-green-600 dark:text-green-400 font-semibold"
        >
          {{ formatNum(f.paid_to_date) }}
        </td>
        <td class="text-center py-2 px-3">
          <span
            class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
            >{{ f.currency }}</span
          >
        </td>
        <td class="py-2 px-3 text-gray-500 max-w-xs truncate">
          {{ f.notes || "—" }}
        </td>
        <td class="text-center py-2 px-3">
          <button
            @click="editEntry(f)"
            class="text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-white text-xs mr-2 transition-colors"
          >
            Edit
          </button>
          <button
            @click="deleteEntry(f.id)"
            class="text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 text-xs transition-colors"
          >
            Delete
          </button>
        </td>
      </template>

      <template #summary>
        <tr
          v-if="financials.data?.length"
          class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/30 font-semibold"
        >
          <td colspan="4" class="py-2 px-3 text-gray-700 dark:text-gray-200">
            Totals (this page)
          </td>
          <td class="text-right py-2 px-3 text-gray-700 dark:text-gray-200">
            {{ formatNum(totalCommitted) }}
          </td>
          <td class="text-right py-2 px-3 text-green-600 dark:text-green-400">
            {{ formatNum(totalPaid) }}
          </td>
          <td colspan="3"></td>
        </tr>
      </template>
    </DataTable>
  </Card>

  <Modal
    :show="showModal"
    :title="editingId ? 'Edit Financial Record' : 'New Financial Record'"
    max-width="lg"
    @close="closeModal"
  >
    <form @submit.prevent="submitEntry" class="space-y-4">
      <Input
        v-model="form.contract_id"
        label="Contract ID"
        placeholder="e.g. CON-2026-001"
        :error="form.errors.contract_id"
      />
      <Select
        v-model="form.pp_project_id"
        :options="projectOptions"
        label="Project"
        placeholder="Select a project"
        searchable
        required
        :error="form.errors.pp_project_id"
      />
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <DatePicker
          v-model="form.as_of_date"
          label="As-Of Date"
          size="md"
          required
          :error="form.errors.as_of_date"
        />
        <Select
          v-model="form.currency"
          :options="[
            { value: 'USD', label: 'USD' },
            { value: 'ZMW', label: 'ZMW' },
          ]"
          label="Currency"
          required
          :error="form.errors.currency"
        />
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <Input
          v-model="form.committed_amount"
          type="number"
          step="0.01"
          min="0"
          label="Committed Amount"
          :error="form.errors.committed_amount"
        />
        <Input
          v-model="form.paid_to_date"
          type="number"
          step="0.01"
          min="0"
          label="Paid-to-Date"
          :error="form.errors.paid_to_date"
        />
      </div>
      <Textarea
        v-model="form.notes"
        label="Notes"
        :rows="2"
        placeholder="Optional notes..."
      />
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
    entity="financials"
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
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import Card from "@/Components/UI/Card.vue";
import DataTable from "@/Components/UI/DataTable.vue";
import Input from "@/Components/UI/Input.vue";
import DatePicker from "@/Components/UI/DatePicker.vue";
import Select from "@/Components/UI/Select.vue";
import Button from "@/Components/UI/Button.vue";
import Textarea from "@/Components/UI/Textarea.vue";
import Modal from "@/Components/UI/Modal.vue";
import PpImportModal from "@/Components/PpImportModal.vue";

const props = defineProps({
  financials: { type: Object, default: () => ({ data: [], links: [] }) },
  ppProjects: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const currencyFilter = ref(props.filters?.currency || "");
const projectFilter = ref(props.filters?.project_id || "");
const searchQuery = ref(props.filters?.search || "");
const showFilters = ref(false);
let searchTimeout = null;

function handleClickOutside(event) {
  if (
    showFilters.value &&
    !event.target.closest(".filter-dropdown-container")
  ) {
    showFilters.value = false;
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});

watch([currencyFilter, projectFilter], () => {
  applyFilters();
  showFilters.value = false;
});

watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => applyFilters(), 300);
});

const showModal = ref(false);
const showImport = ref(false);
const editingId = ref(null);

const projectOptions = computed(() =>
  props.ppProjects.map((p) => ({
    value: p.id,
    label: `${p.project_code} — ${p.project_name}`,
  })),
);

const projectFilterOptions = computed(() =>
  props.ppProjects.map((p) => ({
    value: p.id,
    label: `${p.project_code} — ${p.project_name}`,
  })),
);

const totalCommitted = computed(
  () =>
    props.financials.data?.reduce(
      (s, f) => s + Number(f.committed_amount || 0),
      0,
    ) || 0,
);
const totalPaid = computed(
  () =>
    props.financials.data?.reduce(
      (s, f) => s + Number(f.paid_to_date || 0),
      0,
    ) || 0,
);

const form = useForm({
  contract_id: "",
  pp_project_id: "",
  as_of_date: new Date().toISOString().slice(0, 10),
  committed_amount: null,
  paid_to_date: null,
  currency: "USD",
  notes: "",
});

function applyFilters() {
  const params = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (currencyFilter.value) params.currency = currencyFilter.value;
  if (projectFilter.value) params.project_id = projectFilter.value;
  router.get("/pp/financials", params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function formatNum(val) {
  if (!val && val !== 0) return "—";
  return Number(val).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

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
    form.put(`/pp/financials/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post("/pp/financials", {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
}

function editEntry(f) {
  editingId.value = f.id;
  form.contract_id = f.contract_id || "";
  form.pp_project_id = f.pp_project_id || "";
  form.as_of_date = f.as_of_date || "";
  form.committed_amount = f.committed_amount;
  form.paid_to_date = f.paid_to_date;
  form.currency = f.currency;
  form.notes = f.notes || "";
  showModal.value = true;
}

function resetForm() {
  editingId.value = null;
  form.reset();
  form.currency = "USD";
  form.as_of_date = new Date().toISOString().slice(0, 10);
}

function deleteEntry(id) {
  if (confirm("Delete this financial record?")) {
    router.delete(`/pp/financials/${id}`, { preserveScroll: true });
  }
}
</script>
