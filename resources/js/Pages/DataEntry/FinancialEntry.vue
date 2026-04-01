<template>
    <AppLayout :directorates="directorates">
        <template #title>Financial Data Entry</template>

        <Breadcrumb :items="[
            { label: 'Dashboard', href: '/dashboard' },
            { label: 'Financial Entry', current: true }
        ]" />

        <Card title="Recent Entries">
            <template #actions>
                <Button variant="primary" size="sm" @click="openModal()">+ New Entry</Button>
            </template>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Directorate</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Category</th>
                            <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Budget</th>
                            <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actual</th>
                            <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Variance</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Period</th>
                            <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in entries.data" :key="entry.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                            <td class="py-2 px-3 text-gray-500">{{ entry.directorate?.code }}</td>
                            <td class="py-2 px-3 font-medium text-gray-900 dark:text-white capitalize">{{ entry.category }}</td>
                            <td class="text-right py-2 px-3">{{ formatCurrency(entry.budget_amount) }}</td>
                            <td class="text-right py-2 px-3">{{ formatCurrency(entry.actual_amount) }}</td>
                            <td class="text-right py-2 px-3 font-medium" :class="variance(entry) >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ variance(entry) >= 0 ? '+' : '' }}{{ variance(entry) }}%
                            </td>
                            <td class="py-2 px-3 text-gray-500">{{ entry.period }}</td>
                            <td class="text-center py-2 px-3">
                                <button @click="editEntry(entry)" class="text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-white text-xs mr-2 transition-colors">Edit</button>
                                <button @click="deleteEntry(entry.id)" class="text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 text-xs transition-colors">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!entries.data?.length">
                            <td colspan="7" class="text-center py-8 text-gray-400 text-sm">No entries yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Form Modal -->
        <Modal :show="showModal" :title="editingId ? 'Edit Financial Entry' : 'New Financial Entry'" max-width="lg" @close="closeModal">
            <form @submit.prevent="submitEntry" class="space-y-4">
                <Select
                    v-model="form.directorate_id"
                    :options="directorates"
                    option-value="id"
                    option-label="code"
                    label="Directorate"
                    placeholder="Select directorate"
                    size="md"
                    required
                    :error="form.errors.directorate_id"
                />

                <Select
                    v-model="form.category"
                    :options="[
                        { value: 'revenue', label: 'Revenue' },
                        { value: 'opex', label: 'Operating Expenditure' },
                        { value: 'capex', label: 'Capital Expenditure' },
                        { value: 'collections', label: 'Collections' },
                        { value: 'debt_service', label: 'Debt Service' },
                    ]"
                    label="Category"
                    placeholder="Select category"
                    size="md"
                    required
                    :error="form.errors.category"
                />

                <div class="grid grid-cols-2 gap-3">
                    <Input
                        v-model="form.budget_amount"
                        type="number"
                        step="0.01"
                        label="Budget Amount"
                        placeholder="0.00"
                        size="md"
                        required
                        :error="form.errors.budget_amount"
                    />
                    <Input
                        v-model="form.actual_amount"
                        type="number"
                        step="0.01"
                        label="Actual Amount"
                        placeholder="0.00"
                        size="md"
                        required
                        :error="form.errors.actual_amount"
                    />
                </div>

                <Input
                    v-model="form.period"
                    type="month"
                    label="Period"
                    size="md"
                    required
                    :error="form.errors.period"
                />

                <Textarea
                    v-model="form.notes"
                    label="Notes"
                    :rows="3"
                    placeholder="Optional notes..."
                />

                <div class="flex items-center gap-3 pt-2">
                    <Button type="submit" variant="primary" size="md" :disabled="form.processing" class="flex-1">
                        {{ form.processing ? 'Saving...' : (editingId ? 'Update Entry' : 'Submit Entry') }}
                    </Button>
                    <Button type="button" variant="secondary" size="md" @click="closeModal" class="flex-1">
                        Cancel
                    </Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Breadcrumb from '@/Components/UI/Breadcrumb.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Button from '@/Components/UI/Button.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Modal from '@/Components/UI/Modal.vue';
import { formatCurrency } from '@/Composables/useFormatters';

const props = defineProps({
    entries: { type: Object, default: () => ({ data: [], links: [] }) },
    directorates: { type: Array, default: () => [] },
});

const showModal = ref(false);
const editingId = ref(null);

const form = useForm({
    directorate_id: '',
    category: '',
    budget_amount: '',
    actual_amount: '',
    period: new Date().toISOString().slice(0, 7),
    notes: '',
});

function variance(entry) {
    if (!entry.budget_amount || entry.budget_amount == 0) return 0;
    return (((entry.actual_amount - entry.budget_amount) / entry.budget_amount) * 100).toFixed(1);
}

function openModal() { resetForm(); showModal.value = true; }
function closeModal() { showModal.value = false; resetForm(); }

function submitEntry() {
    if (editingId.value) {
        form.put(`/data-entry/financial-entries/${editingId.value}`, { preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        form.post('/data-entry/financial-entries', { preserveScroll: true, onSuccess: () => closeModal() });
    }
}

function editEntry(entry) {
    editingId.value = entry.id;
    form.directorate_id = entry.directorate_id;
    form.category = entry.category;
    form.budget_amount = entry.budget_amount;
    form.actual_amount = entry.actual_amount;
    form.period = entry.period;
    form.notes = entry.notes || '';
    showModal.value = true;
}

function resetForm() { editingId.value = null; form.reset(); form.period = new Date().toISOString().slice(0, 7); }
function deleteEntry(id) { if (confirm('Delete this entry?')) router.delete(`/data-entry/financial-entries/${id}`, { preserveScroll: true }); }
</script>
