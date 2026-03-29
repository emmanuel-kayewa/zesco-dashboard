<template>
    <Card title="Workstreams" :hide-title-on-mobile="true">
        <template #actions>
            <div class="flex items-center gap-2">
                <Button variant="secondary" size="sm" @click="showImport = true">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </Button>
                <Button variant="primary" size="sm" @click="openModal()">+ New Workstream</Button>
            </div>
        </template>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Project</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Workstream</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 text-nowrap uppercase">Planned %</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 text-nowrap uppercase">Actual %</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Variance</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="w in workstreams.data" :key="w.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                        <td class="py-2 px-3 font-mono text-xs text-gray-500">{{ w.workstream_code }}</td>
                        <td class="py-2 px-3 text-gray-700 dark:text-gray-200">{{ w.project?.project_code || '—' }}</td>
                        <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">{{ w.workstream }}</td>
                        <td class="text-right py-2 px-3 text-gray-500">{{ w.planned_pct != null ? w.planned_pct + '%' : '—' }}</td>
                        <td class="text-right py-2 px-3 font-semibold text-gray-700 dark:text-gray-200">{{ w.actual_pct != null ? w.actual_pct + '%' : '—' }}</td>
                        <td class="text-right py-2 px-3" :class="(w.variance_pct || 0) < 0 ? 'text-red-600' : 'text-green-600'">
                            {{ w.variance_pct != null ? w.variance_pct + '%' : '—' }}
                        </td>
                        <td class="text-center py-2 px-3">
                            <Badge variant="dot" :color="getStatusColor(w.status)" :label="w.status || '—'" />
                        </td>
                        <td class="text-center py-2 px-3">
                            <button @click="editEntry(w)" class="text-zesco-600 hover:text-zesco-800 text-xs mr-2">Edit</button>
                            <button @click="deleteEntry(w.id)" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!workstreams.data?.length">
                        <td colspan="8" class="text-center py-8 text-gray-400 text-sm">No workstreams yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="workstreams.links?.length > 3" class="flex items-center justify-center gap-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <template v-for="link in workstreams.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs" :class="link.active ? 'bg-zesco-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" v-html="link.label" />
                <span v-else class="px-3 py-1 text-xs text-gray-400" v-html="link.label" />
            </template>
        </div>
    </Card>

    <Modal :show="showModal" :title="editingId ? 'Edit Workstream' : 'New Workstream'" max-width="lg" @close="closeModal">
        <form @submit.prevent="submitEntry" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <Input v-model="form.workstream_code" label="Workstream Code" placeholder="e.g. WS-GEN-001-ENG" required :error="form.errors.workstream_code" />
                <Select v-model="form.pp_project_id" :options="projectOptions" label="Project" required :error="form.errors.pp_project_id" />
            </div>
            <Select v-model="form.workstream" :options="workstreamOpts" label="Workstream" required :error="form.errors.workstream" />
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <Input v-model="form.planned_pct" type="number" step="0.01" min="0" max="100" label="Planned (%)" :error="form.errors.planned_pct" />
                <Input v-model="form.actual_pct" type="number" step="0.01" min="0" max="100" label="Actual (%)" :error="form.errors.actual_pct" />
                <Input v-model="form.variance_pct" type="number" step="0.01" label="Variance (%)" :error="form.errors.variance_pct" />
            </div>
            <Select v-model="form.status" :options="statusOpts" label="Status" :error="form.errors.status" />
            <Textarea v-model="form.comments" label="Comments" :rows="2" placeholder="Optional comments..." />
            <div class="flex items-center gap-3 pt-2">
                <Button type="submit" variant="primary" size="md" :disabled="form.processing" class="flex-1">
                    {{ form.processing ? 'Saving...' : (editingId ? 'Update' : 'Create') }}
                </Button>
                <Button type="button" variant="secondary" size="md" @click="closeModal" class="flex-1">Cancel</Button>
            </div>
        </form>
    </Modal>

    <!-- Import Modal -->
    <PpImportModal :show="showImport" entity="workstreams" @close="showImport = false" @imported="() => { showImport = false; router.reload(); }" />
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Button from '@/Components/UI/Button.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Modal from '@/Components/UI/Modal.vue';
import Badge from '@/Components/UI/Badge.vue';
import PpImportModal from '@/Components/PpImportModal.vue';

const props = defineProps({
    workstreams: { type: Object, default: () => ({ data: [], links: [] }) },
    ppProjects:  { type: Array, default: () => [] },
});

const showModal = ref(false);
const showImport = ref(false);
const editingId = ref(null);

const projectOptions = computed(() => props.ppProjects.map(p => ({ value: p.id, label: `${p.project_code} — ${p.project_name}` })));
const workstreamOpts = [
    { value: 'Engineering', label: 'Engineering' },
    { value: 'Procurement', label: 'Procurement' },
    { value: 'Construction', label: 'Construction' },
    { value: 'Commissioning', label: 'Commissioning' },
];
const statusOpts = [
    { value: 'On Track', label: 'On Track' },
    { value: 'Behind', label: 'Behind' },
    { value: 'Ahead', label: 'Ahead' },
    { value: 'Complete', label: 'Complete' },
];

function getStatusColor(status) {
    const map = { 'On Track': 'green', 'Behind': 'red', 'Ahead': 'blue', 'Complete': 'gray' };
    return map[status] || 'gray';
}

const form = useForm({
    workstream_code: '', pp_project_id: '', workstream: '', planned_pct: null, actual_pct: null,
    variance_pct: null, status: '', comments: '',
});

function openModal() { resetForm(); showModal.value = true; }
function closeModal() { showModal.value = false; resetForm(); }

function submitEntry() {
    if (editingId.value) {
        form.put(`/pp/workstreams/${editingId.value}`, { preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        form.post('/pp/workstreams', { preserveScroll: true, onSuccess: () => closeModal() });
    }
}

function editEntry(w) {
    editingId.value = w.id;
    Object.keys(form.data()).forEach(k => {
        if (k in w) form[k] = w[k] ?? (typeof form[k] === 'number' ? null : '');
    });
    showModal.value = true;
}

function resetForm() {
    editingId.value = null;
    form.reset();
}

function deleteEntry(id) {
    if (confirm('Delete this workstream?')) {
        router.delete(`/pp/workstreams/${id}`, { preserveScroll: true });
    }
}
</script>
