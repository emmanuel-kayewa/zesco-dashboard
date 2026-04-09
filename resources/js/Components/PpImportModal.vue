<template>
  <Modal
    :show="show"
    title="Import Data"
    max-width="4xl"
    @close="$emit('close')"
  >
    <!-- Step 1: Upload -->
    <div v-if="step === 'upload'" class="space-y-4">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Upload a CSV or Excel file (.csv, .xlsx, .xls). The system will
        auto-detect column mappings.
      </p>
      <div
        @dragover.prevent="dragover = true"
        @dragleave="dragover = false"
        @drop.prevent="handleDrop"
        :class="[
          'border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer',
          dragover
            ? 'border-zesco-500 bg-zesco-50 dark:border-zesco-400 dark:bg-zesco-950/20'
            : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500',
        ]"
        @click="openFilePicker"
      >
        <svg
          class="w-10 h-10 mx-auto mb-3 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"
          />
        </svg>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
          Drop your file here, or
          <span class="font-medium text-zesco-600 dark:text-zesco-400"
            >browse</span
          >
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500">
          Excel (.xlsx, .xls) or CSV
        </p>
        <input
          ref="fileInput"
          type="file"
          accept=".csv,.xlsx,.xls"
          class="hidden"
          @change="handleFileSelect"
        />
      </div>

      <div
        v-if="selectedFile"
        class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70"
      >
        <svg
          class="h-5 w-5 text-green-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="2"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
          />
        </svg>
        <div class="min-w-0 flex-1">
          <p
            class="truncate text-sm font-medium text-gray-900 dark:text-gray-100"
          >
            {{ selectedFile.name }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatFileSize(selectedFile.size) }}
          </p>
        </div>
        <button
          type="button"
          class="text-xs text-gray-400 transition hover:text-red-500"
          @click.stop="clearSelectedFile"
        >
          Remove
        </button>
      </div>

      <div class="flex items-center gap-3">
        <a
          :href="`/pp/import/template/${entity}`"
          class="text-xs text-zesco-600 hover:underline dark:text-zesco-400"
        >
          Download template CSV
        </a>
      </div>
      <div
        v-if="uploading"
        class="text-sm text-gray-500 flex items-center gap-2"
      >
        <svg class="animate-spin h-4 w-4 text-zesco-600" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
            fill="none"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
        Parsing file...
      </div>
      <div v-if="uploadError" class="text-sm text-red-600">
        {{ uploadError }}
      </div>
    </div>

    <!-- Step 2: Preview + Mapping -->
    <div v-else-if="step === 'preview'" class="space-y-4">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-700 dark:text-gray-300">
          <span class="font-semibold">{{ totalRows }}</span> rows detected.
          <span class="font-semibold text-green-600">{{
            Object.keys(mapping).length
          }}</span>
          columns auto-mapped.
        </p>
        <Button variant="secondary" size="sm" @click="step = 'upload'"
          >← Back</Button
        >
      </div>

      <!-- Column mapping summary -->
      <div
        class="max-h-36 overflow-y-auto border rounded-lg p-3 bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
      >
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
          <div
            v-for="(target, source) in mapping"
            :key="source"
            class="flex items-center gap-1"
          >
            <span class="text-gray-500 truncate" :title="source">{{
              source
            }}</span>
            <span class="text-gray-400">→</span>
            <span
              class="text-green-600 dark:text-green-400 font-medium truncate"
              :title="target"
              >{{ target }}</span
            >
          </div>
        </div>
      </div>

      <!-- Preview table -->
      <div
        class="overflow-x-auto max-h-64 border rounded-lg dark:border-gray-700"
      >
        <table class="w-full text-xs">
          <thead class="sticky top-0 bg-gray-100 dark:bg-gray-700">
            <tr>
              <th
                class="py-1.5 px-2 text-left font-semibold text-gray-600 dark:text-gray-300"
              >
                #
              </th>
              <th
                v-for="col in previewColumns"
                :key="col"
                class="py-1.5 px-2 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap"
              >
                {{ col }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in previewData"
              :key="idx"
              class="border-b border-gray-100 dark:border-gray-700/50"
            >
              <td class="py-1 px-2 text-gray-400">{{ idx + 1 }}</td>
              <td
                v-for="col in previewColumns"
                :key="col"
                class="py-1 px-2 text-gray-700 dark:text-gray-300 max-w-[200px] truncate"
                :title="String(row[col] ?? '')"
              >
                {{ row[col] ?? "—" }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <Button
          variant="primary"
          size="md"
          :disabled="importing"
          @click="confirmImport"
          class="flex-1"
        >
          {{ importing ? "Importing..." : `Import ${totalRows} rows` }}
        </Button>
        <Button
          variant="secondary"
          size="md"
          @click="$emit('close')"
          class="flex-1"
          >Cancel</Button
        >
      </div>
    </div>

    <!-- Step 3: Result -->
    <div v-else-if="step === 'result'" class="space-y-4">
      <div class="text-center py-4">
        <div class="text-4xl mb-2">✓</div>
        <p class="text-lg font-semibold text-gray-900 dark:text-white">
          Import Complete
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          <span class="text-green-600 font-semibold">{{
            result.imported
          }}</span>
          imported
          <span v-if="result.skipped" class="ml-2 text-yellow-600"
            >• {{ result.skipped }} skipped</span
          >
        </p>
      </div>
      <div
        v-if="result.errors?.length"
        class="max-h-32 overflow-y-auto border rounded-lg p-3 bg-red-50 dark:bg-red-900/20 dark:border-red-800"
      >
        <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-1">
          Errors:
        </p>
        <p
          v-for="(err, i) in result.errors"
          :key="i"
          class="text-xs text-red-600 dark:text-red-300"
        >
          {{ err }}
        </p>
      </div>
      <Button variant="primary" size="md" @click="finish" class="w-full"
        >Done</Button
      >
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import Modal from "@/Components/UI/Modal.vue";
import Button from "@/Components/UI/Button.vue";

const props = defineProps({
  show: { type: Boolean, default: false },
  entity: { type: String, required: true },
});

const emit = defineEmits(["close", "imported"]);

const step = ref("upload");
const dragover = ref(false);
const fileInput = ref(null);
const selectedFile = ref(null);
const uploading = ref(false);
const uploadError = ref("");
const importing = ref(false);

// Preview data
const totalRows = ref(0);
const mapping = ref({});
const previewData = ref([]);
const previewColumns = computed(() => {
  if (!previewData.value.length) return [];
  return Object.keys(previewData.value[0]);
});

// Result data
const result = ref({ imported: 0, skipped: 0, errors: [] });

// Store raw rows for confirm step
const rawRows = ref([]);

function openFilePicker() {
  fileInput.value?.click();
}

function clearSelectedFile() {
  selectedFile.value = null;
  if (fileInput.value) {
    fileInput.value.value = "";
  }
}

function formatFileSize(bytes) {
  if (!bytes) return "0 KB";
  const kb = bytes / 1024;
  if (kb < 1024) return `${Math.round(kb)} KB`;
  return `${(kb / 1024).toFixed(1)} MB`;
}

async function handleFileSelect(event) {
  const file = event.target.files[0];
  await processFile(file);
}

async function handleDrop(event) {
  dragover.value = false;
  const file = event.dataTransfer.files[0];
  await processFile(file);
}

async function processFile(file) {
  if (!file) return;

  selectedFile.value = file;

  uploading.value = true;
  uploadError.value = "";

  const formData = new FormData();
  formData.append("file", file);
  formData.append("entity", props.entity);

  try {
    const response = await axios.post("/pp/import/parse", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    const data = response.data;
    totalRows.value = data.total_rows;
    mapping.value = data.auto_mapping || {};
    previewData.value = data.preview || [];
    rawRows.value = data.rows || [];
    step.value = "preview";
  } catch (err) {
    uploadError.value = err.response?.data?.message || "Failed to parse file.";
  } finally {
    uploading.value = false;
  }
}

async function confirmImport() {
  importing.value = true;

  try {
    const response = await axios.post("/pp/import/confirm", {
      entity: props.entity,
      rows: rawRows.value,
    });

    result.value = response.data;
    step.value = "result";
  } catch (err) {
    uploadError.value = err.response?.data?.message || "Import failed.";
    step.value = "upload";
  } finally {
    importing.value = false;
  }
}

function finish() {
  step.value = "upload";
  dragover.value = false;
  previewData.value = [];
  rawRows.value = [];
  clearSelectedFile();
  result.value = { imported: 0, skipped: 0, errors: [] };
  emit("imported");
  emit("close");
}
</script>
