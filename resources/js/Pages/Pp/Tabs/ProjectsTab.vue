<template>
    <Card title="Projects Portfolio" :hide-title-on-mobile="true">
        <template #actions>
            <div class="flex items-center gap-2">
                <!-- Mobile: Filter button with dropdown -->
                <div class="relative md:hidden filter-dropdown-container">
                    <Button variant="secondary" size="sm" @click="showFilters = !showFilters">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filters
                        </span>
                    </Button>
                    <!-- Filter dropdown -->
                    <div v-if="showFilters" class="absolute left-0 top-full mt-1 w-56 sm:w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3 space-y-3 z-50">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sector</label>
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
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Stage</label>
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
                        <Button variant="secondary" size="sm" @click="showFilters = false" class="w-full">Close</Button>
                    </div>
                </div>
                
                <!-- Desktop: Inline filters -->
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
                    <svg class="w-4 h-4 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </Button>
                <Button variant="primary" size="sm" @click="openModal()">
                    <span class="hidden md:block">+ New Project</span>
                    <span class="block md:hidden">+ New</span>
                </Button>
            </div>
        </template>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Sector</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Health</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Stage</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Phase</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Cost (USD)</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">MW</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Progress</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in projects.data" :key="p.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                        <td class="py-2 px-3 font-mono text-xs text-gray-500">{{ p.project_code }}</td>
                        <td class="py-2 px-3 font-medium text-gray-900 dark:text-white max-w-xs truncate" :title="p.project_name">{{ p.project_name }}</td>
                        <td class="py-2 px-3 text-gray-500 hidden sm:table-cell">{{ p.sector }}</td>
                        <td class="py-2 px-3">
                            <Badge v-if="p.status" variant="dot" :color="getProjectStatusColor(p.status)" :label="p.status" />
                            <span v-else class="text-gray-400 text-xs">—</span>
                        </td>
                        <td class="py-2 px-3 text-gray-500 hidden lg:table-cell">{{ p.project_stage || '—' }}</td>
                        <td class="py-2 px-3 text-gray-500 hidden lg:table-cell">{{ p.lifecycle_phase || '—' }}</td>
                        <td class="text-right py-2 px-3 text-gray-700 dark:text-gray-200 hidden md:table-cell">{{ formatUsd(p.cost_usd) }}</td>
                        <td class="text-right py-2 px-3 text-gray-700 dark:text-gray-200 hidden lg:table-cell">{{ p.capacity_mw || '—' }}</td>
                        <td class="text-right py-2 px-3 hidden md:table-cell">
                            <span v-if="p.progress_pct !== null" class="font-semibold">{{ p.progress_pct }}%</span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="text-center py-2 px-3">
                            <button @click="editEntry(p)" class="text-zesco-600 hover:text-zesco-800 text-xs mr-2">Edit</button>
                            <button @click="deleteEntry(p.id)" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!projects.data?.length">
                        <td colspan="9" class="text-center py-8 text-gray-400 text-sm">No projects yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="projects.links?.length > 3" class="flex items-center justify-center gap-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <template v-for="link in projects.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs" :class="link.active ? 'bg-zesco-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" v-html="link.label" />
                <span v-else class="px-3 py-1 text-xs text-gray-400" v-html="link.label" />
            </template>
        </div>
    </Card>

    <!-- Modal -->
    <Modal :show="showModal" :title="editingId ? 'Edit Project' : 'New Project'" max-width="4xl" @close="closeModal">
        <form @submit.prevent="submitEntry" class="space-y-4">
            <!-- Section tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 gap-1 overflow-x-auto">
                <button v-for="tab in formTabs" :key="tab.key" type="button"
                    @click="activeFormTab = tab.key"
                    class="px-3 py-2 text-xs font-medium whitespace-nowrap border-b-2 transition-colors"
                    :class="activeFormTab === tab.key ? 'border-zesco-600 text-zesco-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Project Info -->
            <div v-show="activeFormTab === 'info'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <Input v-model="form.project_code" label="Project Code" placeholder="e.g. GEN-001" required :error="form.errors.project_code" />
                    <Input v-model="form.project_name" label="Project Name" placeholder="Project name" required :error="form.errors.project_name" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <Select v-model="form.sector" :options="sectorOptions" label="Sector" required :error="form.errors.sector" />
                    <Input v-model="form.sub_sector" label="Sub-Sector" placeholder="e.g. Utility Scale Solar" :error="form.errors.sub_sector" />
                    <Select v-model="form.project_stage" :options="projectStageOptions" label="Project Stage" required :error="form.errors.project_stage" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <Select v-model="form.status" :options="healthStatusOptions" label="Health Status" :error="form.errors.status" />
                    <Input v-model="form.programme" label="Programme" placeholder="e.g. Renewables" :error="form.errors.programme" />
                    <Input v-model="form.province" label="Province" :error="form.errors.province" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Input v-model="form.district" label="District" :error="form.errors.district" />
                </div>
            </div>

            <!-- Classification (REMs) -->
            <div v-show="activeFormTab === 'classification'">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <Input v-model="form.energy_type" label="Energy Type" placeholder="e.g. Solar PV" :error="form.errors.energy_type" />
                    <Select v-model="form.renewable_flag" :options="boolOptions" label="Renewable" :error="form.errors.renewable_flag" />
                    <Input v-model="form.market_segment" label="Market Segment" placeholder="e.g. Utility" :error="form.errors.market_segment" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <Input v-model="form.ownership_model" label="Ownership Model" placeholder="e.g. ZESCO Owned" :error="form.errors.ownership_model" />
                    <Input v-model="form.owner_group" label="Owner Group" placeholder="e.g. ZESCO" :error="form.errors.owner_group" />
                    <Input v-model="form.owner_entity" label="Owner Entity" :error="form.errors.owner_entity" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Select v-model="form.is_ipp" :options="boolOptions" label="IPP" :error="form.errors.is_ipp" />
                    <Select v-model="form.grid_connected" :options="boolOptions" label="Grid Connected" :error="form.errors.grid_connected" />
                    <Select v-model="form.owner_subsidiary_flag" :options="boolOptions" label="Subsidiary" :error="form.errors.owner_subsidiary_flag" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <Input v-model="form.owner_subsidiary_name" label="Subsidiary Name" :error="form.errors.owner_subsidiary_name" />
                    <Select v-model="form.lifecycle_phase" :options="lifecycleOptions" label="Lifecycle Phase" :error="form.errors.lifecycle_phase" />
                </div>
            </div>

            <!-- People & Schedule -->
            <div v-show="activeFormTab === 'schedule'">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <Input v-model="form.contractor" label="Contractor" :error="form.errors.contractor" />
                    <Input v-model="form.developer" label="Developer" :error="form.errors.developer" />
                    <Input v-model="form.project_manager" label="Project Manager" :error="form.errors.project_manager" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                    <Input v-model="form.planned_start" type="date" label="Planned Start" :error="form.errors.planned_start" />
                    <Input v-model="form.planned_finish" type="date" label="Planned Finish" :error="form.errors.planned_finish" />
                    <Input v-model="form.forecast_finish" type="date" label="Forecast Finish" :error="form.errors.forecast_finish" />
                    <Input v-model="form.cod_planned" type="date" label="COD Planned" :error="form.errors.cod_planned" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Input v-model="form.cod_actual" type="date" label="COD Actual" :error="form.errors.cod_actual" />
                    <Input v-model="form.commissioned_date" type="date" label="Commissioned Date" :error="form.errors.commissioned_date" />
                    <Input v-model="form.last_update_date" type="date" label="Last Update Date" :error="form.errors.last_update_date" />
                </div>
            </div>

            <!-- Financial -->
            <div v-show="activeFormTab === 'financial'">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <Input v-model="form.cost_usd" type="number" step="0.01" min="0" label="Cost (USD)" :error="form.errors.cost_usd" />
                    <Input v-model="form.cost_zmw" type="number" step="0.01" min="0" label="Cost (ZMW)" :error="form.errors.cost_zmw" />
                    <Input v-model="form.approved_budget" type="number" step="0.01" min="0" label="Approved Budget" :error="form.errors.approved_budget" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Input v-model="form.committed_cost" type="number" step="0.01" min="0" label="Committed Cost" :error="form.errors.committed_cost" />
                    <Input v-model="form.actual_spend" type="number" step="0.01" min="0" label="Actual Spend" :error="form.errors.actual_spend" />
                    <Input v-model="form.forecast_at_completion" type="number" step="0.01" min="0" label="Forecast at Completion" :error="form.errors.forecast_at_completion" />
                </div>
            </div>

            <!-- Capacity & Status -->
            <div v-show="activeFormTab === 'capacity'">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <Input v-model="form.capacity_mw" type="number" step="0.001" min="0" label="Capacity (MW)" :error="form.errors.capacity_mw" />
                    <Input v-model="form.commissioned_mw" type="number" step="0.001" min="0" label="Commissioned MW" :error="form.errors.commissioned_mw" />
                    <Input v-model="form.commissioned_mw_to_date" type="number" step="0.001" min="0" label="Commissioned MW to Date" :error="form.errors.commissioned_mw_to_date" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Input v-model="form.commissioned_capacity_mw" type="number" step="0.001" min="0" label="Commissioned Capacity (MW)" :error="form.errors.commissioned_capacity_mw" />
                    <Input v-model="form.progress_pct" type="number" step="0.01" min="0" max="100" label="Progress (%)" :error="form.errors.progress_pct" />
                </div>
            </div>

            <!-- Notes -->
            <div v-show="activeFormTab === 'notes'">
                <div class="space-y-3">
                    <Textarea v-model="form.key_issue_summary" label="Key Issue Summary" :rows="3" placeholder="Key issues or notes..." />
                    <Textarea v-model="form.next_decision" label="Next Key Decision" :rows="2" placeholder="Next key decision or milestone..." />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <Button type="submit" variant="primary" size="md" :disabled="form.processing" class="flex-1">
                    {{ form.processing ? 'Saving...' : (editingId ? 'Update Project' : 'Create Project') }}
                </Button>
                <Button type="button" variant="secondary" size="md" @click="closeModal" class="flex-1">Cancel</Button>
            </div>
        </form>
    </Modal>

    <!-- Import Modal -->
    <PpImportModal :show="showImport" entity="projects" @close="showImport = false" @imported="() => { showImport = false; router.reload(); }" />
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Button from '@/Components/UI/Button.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Modal from '@/Components/UI/Modal.vue';
import Badge from '@/Components/UI/Badge.vue';
import PpImportModal from '@/Components/PpImportModal.vue';
import { useBadges } from '@/Composables/useBadges';

const { getProjectStatusColor } = useBadges();

const props = defineProps({
    projects: { type: Object, default: () => ({ data: [], links: [] }) },
    filters:  { type: Object, default: () => ({}) },
});

const sectorFilter = ref(props.filters?.sector || '');
const statusFilter = ref(props.filters?.status || '');
const showFilters = ref(false);
const showImport = ref(false);
const activeFormTab = ref('info');

// Close filter dropdown when clicking outside
function handleClickOutside(event) {
    if (showFilters.value && !event.target.closest('.filter-dropdown-container')) {
        showFilters.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch([sectorFilter, statusFilter], () => {
    applyFilters();
    showFilters.value = false;
});

const showModal = ref(false);
const editingId = ref(null);

const formTabs = [
    { key: 'info', label: 'Project Info' },
    { key: 'classification', label: 'Classification' },
    { key: 'schedule', label: 'People & Schedule' },
    { key: 'financial', label: 'Financial' },
    { key: 'capacity', label: 'Capacity & Status' },
    { key: 'notes', label: 'Notes' },
];

const sectorOptions = [
    { value: 'Generation', label: 'Generation' },
    { value: 'Transmission', label: 'Transmission' },
    { value: 'Distribution', label: 'Distribution' },
    { value: 'IPP', label: 'IPP' },
];
const projectStageOptions = [
    { value: 'Execution', label: 'Execution' },
    { value: 'Preparation', label: 'Preparation' },
    { value: 'Completed', label: 'Completed' },
    { value: 'Cancelled', label: 'Cancelled' },
    { value: 'Commissioned', label: 'Commissioned' },
];
const healthStatusOptions = [
    { value: 'On Track', label: 'On Track' },
    { value: 'Delayed', label: 'Delayed' },
    { value: 'At Risk', label: 'At Risk' },
];
const lifecycleOptions = [
    { value: 'Implementation', label: 'Implementation' },
    { value: 'Commissioning/Operational', label: 'Commissioning/Operational' },
    { value: 'Procurement', label: 'Procurement' },
    { value: 'Contracting', label: 'Contracting' },
];
const boolOptions = [
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
];

const form = useForm({
    project_code: '', project_name: '', sector: 'Generation', sub_sector: '', project_stage: 'Preparation', status: '',
    programme: '', province: '', district: '', contractor: '', developer: '',
    cost_usd: null, cost_zmw: null, capacity_mw: null, commissioned_mw: null, progress_pct: null,
    cod_planned: '', key_issue_summary: '', last_update_date: '', notes: '',
    // REMs classification
    energy_type: '', renewable_flag: '', market_segment: '', ownership_model: '',
    owner_group: '', owner_entity: '', is_ipp: '', commissioned_mw_to_date: null,
    grid_connected: '', cod_actual: '', commissioned_date: '', owner_subsidiary_name: '',
    owner_subsidiary_flag: '', commissioned_capacity_mw: null, lifecycle_phase: '',
    // PMO
    project_manager: '', planned_start: '', planned_finish: '', forecast_finish: '',
    approved_budget: null, committed_cost: null, actual_spend: null, forecast_at_completion: null,
    next_decision: '',
});

function applyFilters() {
    const params = {};
    if (sectorFilter.value) params.sector = sectorFilter.value;
    if (statusFilter.value) params.project_stage = statusFilter.value;
    router.get('/pp/projects', params, { preserveState: true, preserveScroll: true });
}

function formatUsd(val) {
    if (!val) return '—';
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function openModal() { resetForm(); showModal.value = true; }
function closeModal() { showModal.value = false; resetForm(); }

function submitEntry() {
    if (editingId.value) {
        form.put(`/pp/projects/${editingId.value}`, { preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        form.post('/pp/projects', { preserveScroll: true, onSuccess: () => closeModal() });
    }
}

function editEntry(p) {
    editingId.value = p.id;
    Object.keys(form.data()).forEach(k => {
        if (k in p) form[k] = p[k] ?? (typeof form[k] === 'number' ? null : '');
    });
    activeFormTab.value = 'info';
    showModal.value = true;
}

function resetForm() {
    editingId.value = null;
    form.reset();
    form.sector = 'Generation';
    form.project_stage = 'Preparation';
    activeFormTab.value = 'info';
}

function deleteEntry(id) {
    if (confirm('Delete this project? Related milestones, financials, risks, and safeguards will also be deleted.')) {
        router.delete(`/pp/projects/${id}`, { preserveScroll: true });
    }
}
</script>
