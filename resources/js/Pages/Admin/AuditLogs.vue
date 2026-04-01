<template>
  <AppLayout :directorates="directorates">
    <template #title>Audit Logs</template>

    <Breadcrumb
      :items="[
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'Admin', href: '/admin' },
        { label: 'Audit Logs', current: true },
      ]"
    />

    <!-- Filters -->
    <div class="flex flex-wrap items-end gap-3 mb-6 no-print">
      <Select
        v-model="filterAction"
        :options="[
          { value: '', label: 'All Actions' },
          { value: 'create', label: 'Create' },
          { value: 'update', label: 'Update' },
          { value: 'delete', label: 'Delete' },
          { value: 'login', label: 'Login' },
          { value: 'export', label: 'Export' },
        ]"
        size="md"
        class="w-44"
        @update:modelValue="applyFilter"
      />
      <Input
        v-model="filterSearch"
        placeholder="Search user or description..."
        size="md"
        class="w-64"
        @keyup.enter="applyFilter"
      />
      <Button variant="primary" size="md" @click="applyFilter">Filter</Button>
      <Button variant="secondary" size="md" @click="clearFilter">Clear</Button>
    </div>

    <Card title="Activity Log">
      <DataTable
        :columns="[
          { key: 'timestamp', label: 'Timestamp' },
          { key: 'user', label: 'User' },
          { key: 'action', label: 'Action' },
          { key: 'entity', label: 'Entity', class: 'hidden md:table-cell' },
          { key: 'description', label: 'Description' },
          { key: 'ip', label: 'IP', class: 'hidden lg:table-cell' },
        ]"
        :rows="logs.data"
        :pagination="logs.links"
        empty-message="No audit logs found."
        table-class="min-w-[640px]"
        v-slot="{ row: log }"
      >
        <td class="py-2 px-3 text-gray-400 text-xs whitespace-nowrap">
          {{ log.created_at }}
        </td>
        <td
          class="py-2 px-3 font-medium text-gray-900 dark:text-white text-xs whitespace-nowrap"
        >
          {{ log.user?.name || "System" }}
        </td>
        <td class="py-2 px-3">
          <Badge
            variant="filled"
            :color="getAuditActionColor(log.action)"
            :label="log.action"
          />
        </td>
        <td class="py-2 px-3 text-gray-500 text-xs hidden md:table-cell">
          {{ log.entity_type }}{{ log.entity_id ? ` #${log.entity_id}` : "" }}
        </td>
        <td
          class="py-2 px-3 text-gray-600 dark:text-gray-400 text-xs max-w-[200px] truncate"
          :title="log.description"
        >
          {{ log.description }}
        </td>
        <td class="py-2 px-3 text-gray-400 text-xs hidden lg:table-cell">
          {{ log.ip_address }}
        </td>
      </DataTable>
    </Card>
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import Breadcrumb from "@/Components/UI/Breadcrumb.vue";
import Card from "@/Components/UI/Card.vue";
import DataTable from "@/Components/UI/DataTable.vue";
import Select from "@/Components/UI/Select.vue";
import Input from "@/Components/UI/Input.vue";
import Button from "@/Components/UI/Button.vue";
import Badge from "@/Components/UI/Badge.vue";
import { useBadges } from "@/Composables/useBadges";

const { getAuditActionColor } = useBadges();

const props = defineProps({
  logs: { type: Object, default: () => ({ data: [], links: [] }) },
  directorates: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const filterAction = ref(props.filters?.action || "");
const filterSearch = ref(props.filters?.search || "");

function applyFilter() {
  router.get(
    "/admin/audit-logs",
    {
      action: filterAction.value || undefined,
      search: filterSearch.value || undefined,
    },
    { preserveState: true },
  );
}

function clearFilter() {
  filterAction.value = "";
  filterSearch.value = "";
  router.get("/admin/audit-logs");
}
</script>
