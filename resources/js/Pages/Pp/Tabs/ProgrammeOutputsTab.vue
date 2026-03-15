<template>
    <Card title="Programme Outputs">
        <template #actions>
            <Button variant="primary" size="sm" @click="openModal()">+ New Output</Button>
        </template>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Programme</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Period</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Connections</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Transformers</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Pending Jobs</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Notes</th>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="o in programmeOutputs.data" :key="o.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                        <td class="py-2 px-3 font-mono text-xs text-gray-500">{{ o.output_code }}</td>
                        <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">{{ o.programme }}</td>
                        <td class="py-2 px-3 text-gray-500 hidden sm:table-cell">{{ o.period }}</td>
                        <td class="text-right py-2 px-3 text-gray-700 dark:text-gray-200 font-semibold">{{ formatInt(o.connections_delivered) }}</td>
                        <td class="text-right py-2 px-3 text-gray-700 dark:text-gray-200 hidden md:table-cell">{{ formatInt(o.transformers_energised) }}</td>
                        <td class="text-right py-2 px-3 text-amber-600 dark:text-amber-400 font-semibold hidden lg:table-cell">{{ formatInt(o.jobs_pending_connection) }}</td>
                        <td class="py-2 px-3 text-gray-500 max-w-xs truncate hidden lg:table-cell">{{ o.notes || '—' }}</td>
                        <td class="text-center py-2 px-3">
                            <button @click="editEntry(o)" class="text-zesco-600 hover:text-zesco-800 text-xs mr-2">Edit</button>
                            <button @click="deleteEntry(o.id)" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!programmeOutputs.data?.length">
                        <td colspan="8" class="text-center py-8 text-gray-400 text-sm">No programme outputs yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="programmeOutputs.links?.length > 3" class="flex items-center justify-center gap-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <template v-for="link in programmeOutputs.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs" :class="link.active ? 'bg-zesco-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" v-html="link.label" />
                <span v-else class="px-3 py-1 text-xs text-gray-400" v-html="link.label" />
            </template>
        </div>
    </Card>

    <Modal :show="showModal" :title="editingId ? 'Edit Programme Output' : 'New Programme Output'" max-width="lg" @close="closeModal">
        <form @submit.prevent="submitEntry" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <Input v-model="form.output_code" label="Output Code" placeholder="e.g. OUT-NEAT-Q1-2026" required :error="form.errors.output_code" />
                <Input v-model="form.programme" label="Programme" placeholder="e.g. NEAT" required :error="form.errors.programme" />
            </div>
            <Input v-model="form.period" label="Period" placeholder="e.g. Q4 2025" required :error="form.errors.period" />
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <Input v-model="form.connections_delivered" type="number" min="0" label="Connections Delivered" :error="form.errors.connections_delivered" />
                <Input v-model="form.transformers_energised" type="number" min="0" label="Transformers Energised" :error="form.errors.transformers_energised" />
                <Input v-model="form.jobs_pending_connection" type="number" min="0" label="Jobs Pending" :error="form.errors.jobs_pending_connection" />
            </div>
            <div class="w-full">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notes</label>
                <textarea v-model="form.notes" rows="2" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-gray-500 focus:border-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Optional notes..."></textarea>
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
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    programmeOutputs: { type: Object, default: () => ({ data: [], links: [] }) },
});

const showModal = ref(false);
const editingId = ref(null);

const form = useForm({
    output_code: '', programme: '', period: '',
    connections_delivered: null, transformers_energised: null, jobs_pending_connection: null, notes: '',
});

function formatInt(val) {
    if (val === null || val === undefined) return '—';
    return Number(val).toLocaleString('en-US');
}

function openModal() { resetForm(); showModal.value = true; }
function closeModal() { showModal.value = false; resetForm(); }

function submitEntry() {
    if (editingId.value) {
        form.put(`/pp/programme-outputs/${editingId.value}`, { preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        form.post('/pp/programme-outputs', { preserveScroll: true, onSuccess: () => closeModal() });
    }
}

function editEntry(o) {
    editingId.value = o.id;
    Object.keys(form.data()).forEach(k => { if (k in o) form[k] = o[k] ?? ''; });
    showModal.value = true;
}

function resetForm() {
    editingId.value = null;
    form.reset();
}

function deleteEntry(id) {
    if (confirm('Delete this programme output?')) {
        router.delete(`/pp/programme-outputs/${id}`, { preserveScroll: true });
    }
}
</script>
