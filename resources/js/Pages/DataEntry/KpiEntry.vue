<template>
  <AppLayout :directorates="directorates">
    <template #title>KPI Data Entry</template>

    <Breadcrumb
      :items="[
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'KPI Entry', current: true },
      ]"
    />

    <Card title="Recent Entries">
      <template #actions>
        <Button variant="primary" size="sm" @click="openModal()"
          >+ New Entry</Button
        >
      </template>
      <DataTable
        :columns="[
          { key: 'kpi', label: 'KPI' },
          { key: 'directorate', label: 'Directorate' },
          { key: 'value', label: 'Value', align: 'right' },
          { key: 'period_type', label: 'Period Type' },
          { key: 'date', label: 'Date' },
          { key: 'source', label: 'Source' },
          { key: 'actions', label: 'Actions', align: 'center' },
        ]"
        :rows="entries.data"
        :pagination="entries.links"
        empty-message="No entries yet."
        v-slot="{ row: entry }"
      >
        <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">
          {{ entry.kpi?.name }}
        </td>
        <td class="py-2 px-3 text-gray-500">{{ entry.directorate?.code }}</td>
        <td class="text-right py-2 px-3 font-medium">{{ entry.value }}</td>
        <td class="py-2 px-3 text-gray-500 capitalize">
          {{ entry.period_type }}
        </td>
        <td class="py-2 px-3 text-gray-500">{{ entry.period_date }}</td>
        <td class="py-2 px-3">
          <span
            class="text-xs px-2 py-0.5 rounded-full"
            :class="
              entry.source === 'manual'
                ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400'
            "
          >
            {{ entry.source }}
          </span>
        </td>
        <td class="text-center py-2 px-3">
          <template v-if="entry.source === 'manual'">
            <button
              @click="editEntry(entry)"
              class="text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-white text-xs mr-2 font-medium transition-colors"
            >
              Edit
            </button>
            <button
              @click="deleteEntry(entry.id)"
              class="text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 text-xs font-medium transition-colors"
            >
              Delete
            </button>
          </template>
          <span v-else class="text-xs text-gray-400">—</span>
        </td>
      </DataTable>
    </Card>

    <!-- Form Modal -->
    <Modal
      :show="showModal"
      :title="editingId ? 'Edit KPI Entry' : 'New KPI Entry'"
      max-width="lg"
      @close="closeModal"
    >
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
          v-model="form.kpi_id"
          :options="filteredKpis"
          option-value="id"
          option-label="name"
          label="KPI"
          placeholder="Select KPI"
          size="md"
          required
          :error="form.errors.kpi_id"
        />

        <Input
          v-model="form.value"
          type="number"
          step="0.01"
          label="Value"
          size="md"
          required
          :error="form.errors.value"
        />

        <Select
          v-model="form.period_type"
          :options="['daily', 'weekly', 'monthly', 'quarterly', 'yearly']"
          label="Period Type"
          size="md"
          required
          :error="form.errors.period_type"
        />

        <DatePicker
          v-model="form.period_date"
          label="Period Date"
          required
          :error="form.errors.period_date"
        />

        <Textarea
          v-model="form.notes"
          label="Notes"
          :rows="3"
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
            {{
              form.processing
                ? "Saving..."
                : editingId
                  ? "Update Entry"
                  : "Submit Entry"
            }}
          </Button>
          <Button
            type="button"
            variant="secondary"
            size="md"
            @click="closeModal"
            class="flex-1"
          >
            Cancel
          </Button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import Breadcrumb from "@/Components/UI/Breadcrumb.vue";
import Card from "@/Components/UI/Card.vue";
import DataTable from "@/Components/UI/DataTable.vue";
import Input from "@/Components/UI/Input.vue";
import DatePicker from "@/Components/UI/DatePicker.vue";
import Select from "@/Components/UI/Select.vue";
import Button from "@/Components/UI/Button.vue";
import Textarea from "@/Components/UI/Textarea.vue";
import Modal from "@/Components/UI/Modal.vue";

const props = defineProps({
  entries: { type: Object, default: () => ({ data: [], links: [] }) },
  kpis: { type: Array, default: () => [] },
  directorates: { type: Array, default: () => [] },
});

const showModal = ref(false);
const editingId = ref(null);

const form = useForm({
  kpi_id: "",
  directorate_id: "",
  value: "",
  period_date: new Date().toISOString().slice(0, 10),
  period_type: "monthly",
  notes: "",
});

const filteredKpis = computed(() => {
  if (!form.directorate_id) return props.kpis;
  return props.kpis.filter(
    (k) => !k.directorate_id || k.directorate_id == form.directorate_id,
  );
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
    form.put(`/data-entry/kpi-entries/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post("/data-entry/kpi-entries", {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
}

function editEntry(entry) {
  editingId.value = entry.id;
  form.kpi_id = entry.kpi_id;
  form.directorate_id = entry.directorate_id;
  form.value = entry.value;
  form.period_type = entry.period_type;
  form.period_date = entry.period_date;
  form.notes = entry.notes || "";
  showModal.value = true;
}

function resetForm() {
  editingId.value = null;
  form.reset();
  form.period_date = new Date().toISOString().slice(0, 10);
  form.period_type = "monthly";
}

function deleteEntry(id) {
  if (confirm("Are you sure you want to delete this entry?")) {
    router.delete(`/data-entry/kpi-entries/${id}`, { preserveScroll: true });
  }
}
</script>
