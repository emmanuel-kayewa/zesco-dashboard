import { ref } from 'vue';
import { read, utils } from 'xlsx';

/**
 * Column-header aliases (case-insensitive, trimmed).
 * Maps various header names a user might use to the canonical field name.
 */
const PROJECT_HEADER_MAP = {
    'name':              'name',
    'name of project':   'name',
    'project name':      'name',
    'project':           'name',
    'location':          'location',
    'developer':         'developer',
    'size (mw)':         'size_mw',
    'size_mw':           'size_mw',
    'size mw':           'size_mw',
    'mw':                'size_mw',
    'capacity':          'size_mw',
    'capacity (mw)':     'size_mw',
    'project type':      'project_type',
    'project_type':      'project_type',
    'type':              'project_type',
    'est. completion':   'est_completion',
    'est completion':    'est_completion',
    'est_completion':    'est_completion',
    'estimated completion': 'est_completion',
    'completion':        'est_completion',
    'completion date':   'est_completion',
    'notes':             'notes',
    'note':              'notes',
    'remarks':           'notes',
    'comment':           'notes',
    'comments':          'notes',
};

const NET_METERING_HEADER_MAP = {
    'key initiative':    'key_initiative',
    'key_initiative':    'key_initiative',
    'initiative':        'key_initiative',
    'item':              'key_initiative',
    'status':            'status_value',
    'status_value':      'status_value',
    'status value':      'status_value',
    'value':             'status_value',
};

/**
 * Parse rows from the first sheet of an uploaded file.
 * Returns raw row objects keyed by the original header text.
 */
function parseFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const wb = read(new Uint8Array(e.target.result), { type: 'array' });
                const sheet = wb.Sheets[wb.SheetNames[0]];
                const rows = utils.sheet_to_json(sheet, { defval: '' });
                resolve(rows);
            } catch (err) {
                reject(new Error('Could not parse the file. Please check the format.'));
            }
        };
        reader.onerror = () => reject(new Error('Failed to read file.'));
        reader.readAsArrayBuffer(file);
    });
}

/**
 * Map raw row objects to canonical field names using a header map.
 */
function mapRows(rows, headerMap) {
    return rows.map((row, i) => {
        const mapped = {};
        for (const [rawKey, value] of Object.entries(row)) {
            const normalised = rawKey.trim().toLowerCase();
            const field = headerMap[normalised];
            if (field) {
                mapped[field] = typeof value === 'string' ? value.trim() : value;
            }
        }
        mapped.sort_order = i;
        return mapped;
    });
}

/**
 * Composable for uploading CSV/Excel into a weekly report section.
 */
export function useReportUpload() {
    const uploadError = ref('');
    const uploading = ref(false);

    /**
     * Process a file upload for a project section (completed_solar, construction_projects, transmission_projects).
     * Returns an array of project entry objects ready to assign to form.sections[i].project_entries.
     */
    async function parseProjectFile(file) {
        uploadError.value = '';
        uploading.value = true;
        try {
            const rows = await parseFile(file);
            if (!rows.length) {
                uploadError.value = 'The file appears to be empty.';
                return null;
            }
            const entries = mapRows(rows, PROJECT_HEADER_MAP)
                .filter(e => e.name) // skip rows with no project name
                .map((e, i) => ({
                    name:           e.name || '',
                    location:       e.location || '',
                    developer:      e.developer || '',
                    size_mw:        e.size_mw !== '' && e.size_mw != null ? Number(e.size_mw) || null : null,
                    project_type:   e.project_type || '',
                    est_completion: e.est_completion != null ? String(e.est_completion) : '',
                    notes:          e.notes || '',
                    sort_order:     i,
                }));
            if (!entries.length) {
                uploadError.value = 'No valid rows found. Make sure the file has a "Name" column.';
                return null;
            }
            return entries;
        } catch (err) {
            uploadError.value = err.message;
            return null;
        } finally {
            uploading.value = false;
        }
    }

    /**
     * Process a file upload for a net metering section.
     * Returns an array of net metering entry objects.
     */
    async function parseNetMeteringFile(file) {
        uploadError.value = '';
        uploading.value = true;
        try {
            const rows = await parseFile(file);
            if (!rows.length) {
                uploadError.value = 'The file appears to be empty.';
                return null;
            }
            const entries = mapRows(rows, NET_METERING_HEADER_MAP)
                .filter(e => e.key_initiative)
                .map((e, i) => ({
                    key_initiative: e.key_initiative || '',
                    status_value:   e.status_value != null ? String(e.status_value) : '',
                    sort_order:     i,
                }));
            if (!entries.length) {
                uploadError.value = 'No valid rows found. Make sure the file has a "Key Initiative" column.';
                return null;
            }
            return entries;
        } catch (err) {
            uploadError.value = err.message;
            return null;
        } finally {
            uploading.value = false;
        }
    }

    return { uploadError, uploading, parseProjectFile, parseNetMeteringFile };
}
