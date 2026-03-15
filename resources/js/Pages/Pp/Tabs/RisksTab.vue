<template>
    <Card title="Risks">
        <template #actions>
            <Button variant="primary" size="sm" @click="openModal()">+ New Risk</Button>
        </template>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Project</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Category</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">L</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">I</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">S</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Level</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Owner</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Status</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in risks.data" :key="r.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                        <td class="py-2 px-3 font-mono text-xs text-gray-500">{{ r.risk_code }}</td>
                        <td class="py-2 px-3 text-gray-700 dark:text-gray-200 hidden sm:table-cell">{{ r.project?.project_code || '—' }}</td>
                        <td class="py-2 px-3 text-gray-500 hidden md:table-cell">{{ r.risk_category }}</td>
                        <td class="py-2 px-3 text-gray-900 dark:text-white max-w-sm truncate" :title="r.risk_description">{{ r.risk_description }}</td>
                        <td class="text-center py-2 px-3 hidden lg:table-cell">{{ r.likelihood }}</td>
                        <td class="text-center py-2 px-3 hidden lg:table-cell">{{ r.impact }}</td>
                        <td class="text-center py-2 px-3 font-bold hidden lg:table-cell">{{ r.severity }}</td>
                        <td class="text-center py-2 px-3">
                            <Badge variant="dot" :color="getRagColor(r.risk_level)" :label="r.risk_level" />
                        </td>
                        <td class="py-2 px-3 text-gray-500 hidden md:table-cell">{{ r.owner || '—' }}</td>
                        <td class="text-center py-2 px-3 hidden sm:table-cell">
                            <Badge variant="dot" :color="getRiskStatusColor(r.status)" :label="r.status" />
                        </td>
                        <td class="text-center py-2 px-3">
                            <button @click="editEntry(r)" class="text-zesco-600 hover:text-zesco-800 text-xs mr-2">Edit</button>
                            <button @click="deleteEntry(r.id)" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!risks.data?.length">
                        <td colspan="11" class="text-center py-8 text-gray-400 text-sm">No risks yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="risks.links?.length > 3" class="flex items-center justify-center gap-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <template v-for="link in risks.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs" :class="link.active ? 'bg-zesco-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" v-html="link.label" />
                <span v-else class="px-3 py-1 text-xs text-gray-400" v-html="link.label" />
            </template>
        </div>
    </Card>

    <Modal :show="showModal" :title="editingId ? 'Edit Risk' : 'New Risk'" max-width="2xl" @close="closeModal">
        <form @submit.prevent="submitEntry" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <Input v-model="form.risk_code" label="Risk Code" placeholder="e.g. R-001" required :error="form.errors.risk_code" />
                <Select v-model="form.pp_project_id" :options="projectOptions" label="Project (optional)" :error="form.errors.pp_project_id" />
            </div>
            <Input v-model="form.risk_category" label="Category" placeholder="e.g. Wayleave/Compensation" required :error="form.errors.risk_category" />
            <div class="w-full">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Risk Description</label>
                <textarea v-model="form.risk_description" rows="3" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-gray-500 focus:border-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Describe the risk..." required></textarea>
                <p v-if="form.errors.risk_description" class="text-red-500 text-xs mt-1">{{ form.errors.risk_description }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <Input v-model="form.likelihood" type="number" min="1" max="5" step="1" label="Likelihood (1-5)" required :error="form.errors.likelihood" />
                <Input v-model="form.impact" type="number" min="1" max="5" step="1" label="Impact (1-5)" required :error="form.errors.impact" />
                <Select v-model="form.risk_level" :options="ragOptions" label="Risk Level" required :error="form.errors.risk_level" />
            </div>
            <div class="w-full">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mitigation</label>
                <textarea v-model="form.mitigation" rows="2" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-gray-500 focus:border-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Mitigation plan..."></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <Input v-model="form.owner" label="Owner" :error="form.errors.owner" />
                <Input v-model="form.due_date" type="date" label="Due Date" :error="form.errors.due_date" />
                <Select v-model="form.status" :options="statusOpts" label="Status" required :error="form.errors.status" />
            </div>
            <div class="flex items-center gap-3 pt-2">
                <Button type="submit" variant="primary" size="md" :disabled="form.processing" class="flex-1">
                    {{ form.processing ? 'Saving...' : (editingId ? 'Update' : 'Create') }}
                </Button>
                <Button type="button" variant="secondary" size="md" @click="closeModal" class="flex-1">Cancel</Button>
            </div>
        </form>
    </Modal>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useBadges } from '@/Composables/useBadges';

const { getRiskStatusColor, getRagColor } = useBadges();

const props = defineProps({
    risks:      { type: Object, default: () => ({ data: [], links: [] }) },
    ppProjects: { type: Array, default: () => [] },
});

const showModal = ref(false);
const editingId = ref(null);

const projectOptions = computed(() => [
    { value: '', label: '— Portfolio-wide —' },
    ...props.ppProjects.map(p => ({ value: p.id, label: `${p.project_code} — ${p.project_name}` })),
]);
const ragOptions = [
    { value: 'Red', label: 'Red' }, { value: 'Amber', label: 'Amber' }, { value: 'Green', label: 'Green' },
];
const statusOpts = [
    { value: 'Open', label: 'Open' }, { value: 'Mitigating', label: 'Mitigating' }, { value: 'Closed', label: 'Closed' },
];

const form = useForm({
    risk_code: '', pp_project_id: '', risk_category: '', risk_description: '',
    likelihood: 3, impact: 3, risk_level: 'Amber', mitigation: '', owner: '', due_date: '', status: 'Open', notes: '',
});

function openModal() { resetForm(); showModal.value = true; }
function closeModal() { showModal.value = false; resetForm(); }

function submitEntry() {
    if (editingId.value) {
        form.put(`/pp/risks/${editingId.value}`, { preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        form.post('/pp/risks', { preserveScroll: true, onSuccess: () => closeModal() });
    }
}

function editEntry(r) {
    editingId.value = r.id;
    Object.keys(form.data()).forEach(k => { if (k in r) form[k] = r[k] ?? ''; });
    form.pp_project_id = r.pp_project_id || '';
    showModal.value = true;
}

function resetForm() {
    editingId.value = null;
    form.reset();
    form.likelihood = 3;
    form.impact = 3;
    form.risk_level = 'Amber';
    form.status = 'Open';
}

function deleteEntry(id) {
    if (confirm('Delete this risk?')) {
        router.delete(`/pp/risks/${id}`, { preserveScroll: true });
    }
}
</script>
