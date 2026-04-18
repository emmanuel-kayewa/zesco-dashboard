<template>
  <Card title="Projects Portfolio" :hide-title-on-mobile="true">
    <template #actions>
      <div class="flex items-center gap-2">
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
          <!-- Filter dropdown -->
          <div
            v-if="showFilters"
            class="absolute left-0 top-full mt-1 w-56 sm:w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3 space-y-3 z-50"
          >
            <Input
              v-model="searchQuery"
              icon="search"
              placeholder="Search projects…"
              size="sm"
              class="w-full"
            />
            <div>
              <label
                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                >Sector</label
              >
              <Select
                v-model="sectorFilter"
                :options="[
                  { value: 'Generation', label: 'Generation' },
                  { value: 'Transmission', label: 'Transmission' },
                  { value: 'Distribution', label: 'Distribution' },
                  { value: 'IPP', label: 'IPP' },
                ]"
                placeholder="All Sectors"
                size="sm"
                class="w-full"
              />
            </div>
            <div>
              <label
                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                >Stage</label
              >
              <Select
                v-model="statusFilter"
                :options="[
                  { value: 'Execution', label: 'Execution' },
                  { value: 'Preparation', label: 'Preparation' },
                  { value: 'Completed', label: 'Completed' },
                  { value: 'Cancelled', label: 'Cancelled' },
                  { value: 'Commissioned', label: 'Commissioned' },
                ]"
                placeholder="All Stages"
                size="sm"
                class="w-full"
              />
            </div>
            <Button
              variant="secondary"
              size="sm"
              @click="showFilters = false"
              class="w-full"
              >Close</Button
            >
          </div>
        </div>

        <!-- Desktop: Search + Inline filters -->
        <Input
          v-model="searchQuery"
          icon="search"
          placeholder="Search projects…"
          size="md"
          class="w-48 hidden md:block"
        />
        <Select
          v-model="sectorFilter"
          :options="[
            { value: 'Generation', label: 'Generation' },
            { value: 'Transmission', label: 'Transmission' },
            { value: 'Distribution', label: 'Distribution' },
            { value: 'IPP', label: 'IPP' },
          ]"
          placeholder="All Sectors"
          size="md"
          class="w-36 hidden md:block"
        />
        <Select
          v-model="statusFilter"
          :options="[
            { value: 'Execution', label: 'Execution' },
            { value: 'Preparation', label: 'Preparation' },
            { value: 'Completed', label: 'Completed' },
            { value: 'Cancelled', label: 'Cancelled' },
            { value: 'Commissioned', label: 'Commissioned' },
          ]"
          placeholder="All Stages"
          size="md"
          class="w-36 hidden md:block"
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
          <span class="hidden md:block">+ New Project</span>
          <span class="block md:hidden">+ New</span>
        </Button>
      </div>
    </template>

    <DataTable
      :columns="[
        { key: 'code', label: 'Code' },
        { key: 'name', label: 'Name' },
        { key: 'sector', label: 'Sector' },
        { key: 'health', label: 'Health' },
        { key: 'stage', label: 'Stage' },
        { key: 'phase', label: 'Phase' },
        { key: 'cost', label: 'Cost (USD)', align: 'right' },
        { key: 'mw', label: 'MW', align: 'right' },
        { key: 'progress', label: 'Progress', align: 'right' },
        { key: 'actions', label: 'Actions', align: 'center' },
      ]"
      :rows="projects.data"
      :pagination="projects.links"
      empty-message="No projects yet."
      v-slot="{ row: p }"
    >
      <td class="py-2 px-3 font-mono text-xs text-gray-500">
        {{ p.project_code }}
      </td>
      <td
        class="py-2 px-3 font-medium text-gray-900 dark:text-white max-w-xs truncate"
        :title="p.project_name"
      >
        {{ p.project_name }}
      </td>
      <td class="py-2 px-3 text-gray-500">{{ p.sector }}</td>
      <td class="py-2 px-3">
        <Badge
          v-if="p.status"
          variant="dot"
          :color="getProjectStatusColor(p.status)"
          :label="p.status"
        />
        <span v-else class="text-gray-400 text-xs">—</span>
      </td>
      <td class="py-2 px-3 text-gray-500">{{ p.project_stage || "—" }}</td>
      <td class="py-2 px-3 text-gray-500">{{ p.lifecycle_phase || "—" }}</td>
      <td class="text-right py-2 px-3 text-gray-700 dark:text-gray-200">
        {{ formatUsd(p.cost_usd) }}
      </td>
      <td class="text-right py-2 px-3 text-gray-700 dark:text-gray-200">
        {{ p.capacity_mw || "—" }}
      </td>
      <td class="text-right py-2 px-3">
        <span v-if="p.progress_pct !== null" class="font-semibold"
          >{{ p.progress_pct }}%</span
        >
        <span v-else class="text-gray-400">—</span>
      </td>
      <td class="text-center py-2 px-3">
        <button
          @click="editEntry(p)"
          class="text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-white text-xs mr-2 transition-colors"
        >
          Edit
        </button>
        <button
          @click="deleteEntry(p.id)"
          class="text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 text-xs transition-colors"
        >
          Delete
        </button>
      </td>
    </DataTable>
  </Card>

  <!-- Modal -->
  <Modal
    :show="showModal"
    :title="editingId ? 'Edit Project' : 'New Project'"
    max-width="4xl"
    @close="closeModal"
  >
    <form @submit.prevent="submitEntry" class="space-y-4">
      <!-- Section tabs -->
      <SectionTabs
        v-model="activeFormTab"
        :tabs="formTabs"
        aria-label="Project Form Tabs"
      />

      <!-- Project Info -->
      <div v-show="activeFormTab === 'info'">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
          <Input
            v-model="form.project_code"
            label="Project Code"
            placeholder="e.g. GEN-001"
            required
            :error="form.errors.project_code"
          />
          <Input
            v-model="form.project_name"
            label="Project Name"
            placeholder="Project name"
            required
            :error="form.errors.project_name"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-3 gap-3 mb-3">
          <Select
            v-model="form.sector"
            :options="sectorOptions"
            label="Sector"
            required
            :error="form.errors.sector"
          />
          <Input
            v-model="form.sub_sector"
            label="Sub-Sector"
            placeholder="e.g. Utility Scale Solar"
            :error="form.errors.sub_sector"
          />
          <Select
            v-model="form.project_stage"
            :options="projectStageOptions"
            label="Project Stage"
            required
            :error="form.errors.project_stage"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 mb-3">
          <Select
            v-model="form.status"
            :options="healthStatusOptions"
            label="Health Status"
            :error="form.errors.status"
          />
          <Input
            v-model="form.programme"
            label="Programme"
            placeholder="e.g. Renewables"
            :error="form.errors.programme"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3">
          <Select
            v-model="form.province"
            :options="provinceOptions"
            label="Province"
            placeholder="Select Province"
            :error="form.errors.province"
          />
          <Select
            v-model="form.district"
            :options="districtOptions"
            label="District"
            placeholder="Select District"
            :disabled="!form.province"
            :error="form.errors.district"
          />
        </div>
      </div>

      <!-- Classification (REMs) -->
      <div v-show="activeFormTab === 'classification'">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
          <Input
            v-model="form.energy_type"
            label="Energy Type"
            placeholder="e.g. Solar PV"
            :error="form.errors.energy_type"
          />
          <Select
            v-model="form.renewable_flag"
            :options="boolOptions"
            label="Renewable"
            :error="form.errors.renewable_flag"
          />
          <Input
            v-model="form.market_segment"
            label="Market Segment"
            placeholder="e.g. Utility"
            :error="form.errors.market_segment"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
          <Input
            v-model="form.ownership_model"
            label="Ownership Model"
            placeholder="e.g. ZESCO Owned"
            :error="form.errors.ownership_model"
          />
          <Input
            v-model="form.owner_group"
            label="Owner Group"
            placeholder="e.g. ZESCO"
            :error="form.errors.owner_group"
          />
          <Input
            v-model="form.owner_entity"
            label="Owner Entity"
            :error="form.errors.owner_entity"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <Select
            v-model="form.is_ipp"
            :options="boolOptions"
            label="IPP"
            :error="form.errors.is_ipp"
          />
          <Select
            v-model="form.grid_connected"
            :options="boolOptions"
            label="Grid Connected"
            :error="form.errors.grid_connected"
          />
          <Select
            v-model="form.owner_subsidiary_flag"
            :options="boolOptions"
            label="Subsidiary"
            :error="form.errors.owner_subsidiary_flag"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
          <Input
            v-model="form.owner_subsidiary_name"
            label="Subsidiary Name"
            :error="form.errors.owner_subsidiary_name"
          />
          <Select
            v-model="form.lifecycle_phase"
            :options="lifecycleOptions"
            label="Lifecycle Phase"
            :error="form.errors.lifecycle_phase"
          />
        </div>
      </div>

      <!-- People & Schedule -->
      <div v-show="activeFormTab === 'schedule'">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
          <Input
            v-model="form.contractor"
            label="Contractor"
            :error="form.errors.contractor"
          />
          <Input
            v-model="form.developer"
            label="Developer"
            :error="form.errors.developer"
          />
          <Input
            v-model="form.project_manager"
            label="Project Manager"
            :error="form.errors.project_manager"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
          <DatePicker
            v-model="form.planned_start"
            label="Planned Start"
            size="md"
            :error="form.errors.planned_start"
          />
          <DatePicker
            v-model="form.planned_finish"
            label="Planned Finish"
            size="md"
            :error="form.errors.planned_finish"
          />
          <DatePicker
            v-model="form.forecast_finish"
            label="Forecast Finish"
            size="md"
            :error="form.errors.forecast_finish"
          />
          <DatePicker
            v-model="form.cod_planned"
            label="COD Planned"
            size="md"
            :error="form.errors.cod_planned"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <DatePicker
            v-model="form.cod_actual"
            label="COD Actual"
            size="md"
            :error="form.errors.cod_actual"
          />
          <DatePicker
            v-model="form.commissioned_date"
            label="Commissioned Date"
            size="md"
            :error="form.errors.commissioned_date"
          />
          <DatePicker
            v-model="form.last_update_date"
            label="Last Update Date"
            size="md"
            :error="form.errors.last_update_date"
          />
        </div>
      </div>

      <!-- Financial -->
      <div v-show="activeFormTab === 'financial'">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3">
          <Input
            v-model="form.cost_usd"
            type="number"
            step="0.01"
            min="0"
            label="Cost (USD)"
            label-class="truncate"
            :error="form.errors.cost_usd"
          />
          <!-- <Input
            v-model="form.committed_cost"
            type="number"
            step="0.01"
            min="0"
            label="Committed Cost"
            label-class="truncate"
            :error="form.errors.committed_cost"
          /> -->
          <Input
            v-model="form.actual_spend"
            type="number"
            step="0.01"
            min="0"
            label="Actual Spend"
            label-class="truncate"
            :error="form.errors.actual_spend"
          />
        </div>
      </div>

      <!-- Capacity & Status -->
      <div v-show="activeFormTab === 'capacity'">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
          <Input
            v-model="form.capacity_mw"
            type="number"
            step="0.001"
            min="0"
            label="Capacity (MW)"
            label-class="truncate"
            :error="form.errors.capacity_mw"
          />
          <Input
            v-model="form.commissioned_mw"
            type="number"
            step="0.001"
            min="0"
            label="Commissioned MW"
            label-class="truncate"
            :error="form.errors.commissioned_mw"
          />
          <Input
            v-model="form.commissioned_mw_to_date"
            type="number"
            step="0.001"
            min="0"
            label="Commissioned MW to Date"
            label-class="truncate"
            :error="form.errors.commissioned_mw_to_date"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <Input
            v-model="form.commissioned_capacity_mw"
            type="number"
            step="0.001"
            min="0"
            label="Commissioned Capacity (MW)"
            label-class="truncate"
            :error="form.errors.commissioned_capacity_mw"
          />
          <Input
            v-model="form.progress_pct"
            type="number"
            step="0.01"
            min="0"
            max="100"
            label="Progress (%)"
            label-class="truncate"
            :error="form.errors.progress_pct"
          />
        </div>
      </div>

      <!-- Notes -->
      <div v-show="activeFormTab === 'notes'">
        <div class="space-y-3">
          <Textarea
            v-model="form.key_issue_summary"
            label="Key Issue Summary"
            :rows="3"
            placeholder="Key issues or notes..."
          />
          <Textarea
            v-model="form.next_decision"
            label="Next Key Decision"
            :rows="2"
            placeholder="Next key decision or milestone..."
          />
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <Button
          type="submit"
          variant="primary"
          size="md"
          :disabled="form.processing"
          class="flex-1"
        >
          {{
            form.processing
              ? "Saving..."
              : editingId
                ? "Update Project"
                : "Create Project"
          }}
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
    entity="projects"
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
import Badge from "@/Components/UI/Badge.vue";
import SectionTabs from "@/Components/UI/SectionTabs.vue";
import PpImportModal from "@/Components/PpImportModal.vue";
import { useBadges } from "@/Composables/useBadges";

const { getProjectStatusColor } = useBadges();

const props = defineProps({
  projects: { type: Object, default: () => ({ data: [], links: [] }) },
  filters: { type: Object, default: () => ({}) },
});

const sectorFilter = ref(props.filters?.sector || "");
const statusFilter = ref(props.filters?.status || "");
const searchQuery = ref(props.filters?.search || "");
const showFilters = ref(false);
let searchTimeout = null;
const showImport = ref(false);
const activeFormTab = ref("info");

// Close filter dropdown when clicking outside
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

watch([sectorFilter, statusFilter], () => {
  applyFilters();
  showFilters.value = false;
});

watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => applyFilters(), 300);
});

const showModal = ref(false);
const editingId = ref(null);

const formTabs = [
  { key: "info", label: "Project Info" },
  { key: "classification", label: "Classification" },
  { key: "schedule", label: "People & Schedule" },
  { key: "financial", label: "Financial" },
  { key: "capacity", label: "Capacity & Status" },
  { key: "notes", label: "Notes" },
];

const sectorOptions = [
  { value: "Generation", label: "Generation" },
  { value: "Transmission", label: "Transmission" },
  { value: "Distribution", label: "Distribution" },
  { value: "IPP", label: "IPP" },
];
const projectStageOptions = [
  { value: "Execution", label: "Execution" },
  { value: "Preparation", label: "Preparation" },
  { value: "Completed", label: "Completed" },
  { value: "Cancelled", label: "Cancelled" },
  { value: "Commissioned", label: "Commissioned" },
];
const healthStatusOptions = [
  { value: "On Track", label: "On Track" },
  { value: "Delayed", label: "Delayed" },
  { value: "At Risk", label: "At Risk" },
];
const lifecycleOptions = [
  { value: "Implementation", label: "Implementation" },
  { value: "Commissioning/Operational", label: "Commissioning/Operational" },
  { value: "Procurement", label: "Procurement" },
  { value: "Contracting", label: "Contracting" },
];
const boolOptions = [
  { value: "1", label: "Yes" },
  { value: "0", label: "No" },
];

const provinceDistrictMap = {
  Central: ["Chibombo", "Chisamba", "Chitambo", "Kabwe", "Kapiri Mposhi", "Luano", "Mkushi", "Mumbwa", "Ngabwe", "Serenje", "Shibuyunji"],
  Copperbelt: ["Chililabombwe", "Chingola", "Kalulushi", "Kitwe", "Luanshya", "Lufwanyama", "Masaiti", "Mpongwe", "Mufulira", "Ndola"],
  Eastern: ["Chadiza", "Chama", "Chipangali", "Chipata", "Kasenengwa", "Katete", "Lumezi", "Lundazi", "Lusangazi", "Mambwe", "Nyimba", "Petauke", "Sinda", "Vubwi"],
  Luapula: ["Chembe", "Chienge", "Chipili", "Kawambwa", "Lunga", "Mansa", "Milenge", "Mwansabombwe", "Mwense", "Nchelenge", "Samfya"],
  Lusaka: ["Chilanga", "Chirundu", "Chongwe", "Kafue", "Luangwa", "Lusaka", "Rufunsa"],
  Muchinga: ["Chinsali", "Isoka", "Kanchibiya", "Lavushimanda", "Mafinga", "Mpika", "Nakonde", "Shiwangandu"],
  Northern: ["Chilubi", "Kaputa", "Kasama", "Luwingu", "Mbala", "Mporokoso", "Mpulungu", "Mungwi", "Nsama", "Senga Hill"],
  "North-Western": ["Chavuma", "Ikelenge", "Kabompo", "Kalumbila", "Kasempa", "Manyinga", "Mufumbwe", "Mushindamo", "Mwinilunga", "Solwezi", "Zambezi"],
  Southern: ["Batoka", "Chikankata", "Choma", "Gwembe", "Itezhi-Tezhi", "Kalomo", "Kazungula", "Livingstone", "Mapatizya", "Mazabuka", "Monze", "Namwala", "Pemba", "Sinazongwe", "Siavonga", "Zimba"],
  Western: ["Kalabo", "Kaoma", "Limulunga", "Luampa", "Lukulu", "Mitete", "Mongu", "Mulobezi", "Mwandi", "Nalolo", "Nkeyema", "Senanga", "Sesheke", "Shangombo", "Sikongo", "Sioma"],
};

const provinceOptions = Object.keys(provinceDistrictMap).map((p) => ({ value: p, label: p }));

const form = useForm({
  project_code: "",
  project_name: "",
  sector: "Generation",
  sub_sector: "",
  project_stage: "Preparation",
  status: "",
  programme: "",
  province: "",
  district: "",
  contractor: "",
  developer: "",
  cost_usd: null,
  cost_zmw: null,
  capacity_mw: null,
  commissioned_mw: null,
  progress_pct: null,
  cod_planned: "",
  key_issue_summary: "",
  last_update_date: "",
  notes: "",
  // REMs classification
  energy_type: "",
  renewable_flag: "",
  market_segment: "",
  ownership_model: "",
  owner_group: "",
  owner_entity: "",
  is_ipp: "",
  commissioned_mw_to_date: null,
  grid_connected: "",
  cod_actual: "",
  commissioned_date: "",
  owner_subsidiary_name: "",
  owner_subsidiary_flag: "",
  commissioned_capacity_mw: null,
  lifecycle_phase: "",
  // PMO
  project_manager: "",
  planned_start: "",
  planned_finish: "",
  forecast_finish: "",
  approved_budget: null,
  committed_cost: null,
  actual_spend: null,
  forecast_at_completion: null,
  next_decision: "",
});

const districtOptions = computed(() => {
  const province = form.province;
  if (!province || !provinceDistrictMap[province]) return [];
  return provinceDistrictMap[province].map((d) => ({ value: d, label: d }));
});

watch(() => form.province, (newVal, oldVal) => {
  if (oldVal && newVal !== oldVal) form.district = "";
});

function applyFilters() {
  const params = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (sectorFilter.value) params.sector = sectorFilter.value;
  if (statusFilter.value) params.project_stage = statusFilter.value;
  router.get("/pp/projects", params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function formatUsd(val) {
  if (!val) return "—";
  return (
    "$" +
    Number(val).toLocaleString("en-US", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    })
  );
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
    form.put(`/pp/projects/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post("/pp/projects", {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
}

function editEntry(p) {
  editingId.value = p.id;
  Object.keys(form.data()).forEach((k) => {
    if (k in p) form[k] = p[k] ?? (typeof form[k] === "number" ? null : "");
  });
  activeFormTab.value = "info";
  showModal.value = true;
}

function resetForm() {
  editingId.value = null;
  form.reset();
  form.sector = "Generation";
  form.project_stage = "Preparation";
  activeFormTab.value = "info";
}

function deleteEntry(id) {
  if (
    confirm(
      "Delete this project? Related milestones, financials, risks, and safeguards will also be deleted.",
    )
  ) {
    router.delete(`/pp/projects/${id}`, { preserveScroll: true });
  }
}
</script>
