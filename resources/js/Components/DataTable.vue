<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true
    // { key: string, label: string, sortable?: boolean, align?: 'left'|'center'|'right', width?: string }
  },
  data: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  emptyText: {
    type: String,
    default: 'Aucune donnée disponible'
  },
  striped: {
    type: Boolean,
    default: true
  },
  hover: {
    type: Boolean,
    default: true
  },
  sortable: {
    type: Boolean,
    default: false
  },
  selectable: {
    type: Boolean,
    default: false
  },
  selectedKeys: {
    type: Array,
    default: () => []
  },
  rowKey: {
    type: String,
    default: 'id'
  },
  animate: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['sort', 'row-click', 'selection-change']);

const sortColumn = ref(null);
const sortDirection = ref('asc');
const selectedRows = ref(new Set(props.selectedKeys));

const handleSort = (column) => {
  if (!props.sortable || !column.sortable) return;
  
  if (sortColumn.value === column.key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = column.key;
    sortDirection.value = 'asc';
  }
  
  emit('sort', { column: column.key, direction: sortDirection.value });
};

const handleRowClick = (row, event) => {
  emit('row-click', row, event);
};

const toggleSelection = (row) => {
  const key = row[props.rowKey];
  if (selectedRows.value.has(key)) {
    selectedRows.value.delete(key);
  } else {
    selectedRows.value.add(key);
  }
  emit('selection-change', Array.from(selectedRows.value));
};

const isSelected = (row) => {
  return selectedRows.value.has(row[props.rowKey]);
};

const allSelected = computed(() => {
  return props.data.length > 0 && props.data.every(row => selectedRows.value.has(row[props.rowKey]));
});

const toggleAll = () => {
  if (allSelected.value) {
    selectedRows.value.clear();
  } else {
    props.data.forEach(row => selectedRows.value.add(row[props.rowKey]));
  }
  emit('selection-change', Array.from(selectedRows.value));
};

const getRowAnimationDelay = (index) => {
  if (!props.animate) return 0;
  return Math.min(index * 30, 300); // Cap at 300ms
};
</script>

<template>
  <div class="data-table-wrapper">
    <table class="data-table" :class="{ 'data-table--striped': striped, 'data-table--hover': hover }">
      <thead class="data-table__head">
        <tr>
          <!-- Selection Header -->
          <th v-if="selectable" class="data-table__th data-table__th--checkbox">
            <label class="checkbox-wrapper">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleAll"
                class="checkbox-input"
              />
              <span class="checkbox-custom"></span>
            </label>
          </th>
          
          <!-- Column Headers -->
          <th
            v-for="column in columns"
            :key="column.key"
            class="data-table__th"
            :class="{
              'data-table__th--sortable': sortable && column.sortable,
              'data-table__th--sorted': sortColumn === column.key,
              [`data-table__th--${column.align || 'left'}`]: true
            }"
            :style="column.width ? { width: column.width } : {}"
            @click="handleSort(column)"
          >
            <span class="data-table__th-content">
              {{ column.label }}
              
              <!-- Sort Icon -->
              <span
                v-if="sortable && column.sortable"
                class="sort-icon"
                :class="{ 'sort-icon--active': sortColumn === column.key }"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  :class="{ 'sort-icon--asc': sortColumn === column.key && sortDirection === 'asc' }"
                >
                  <path d="M18 15l-6-6-6 6" />
                </svg>
              </span>
            </span>
          </th>
        </tr>
      </thead>
      
      <tbody class="data-table__body">
        <!-- Loading State -->
        <tr v-if="loading">
          <td :colspan="columns.length + (selectable ? 1 : 0)" class="data-table__cell data-table__cell--loading">
            <div class="loading-state">
              <div class="loading-spinner"></div>
              <span>Chargement...</span>
            </div>
          </td>
        </tr>
        
        <!-- Empty State -->
        <tr v-else-if="data.length === 0">
          <td :colspan="columns.length + (selectable ? 1 : 0)" class="data-table__cell data-table__cell--empty">
            <div class="empty-state">
              <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M3 9h18M9 21V9" />
              </svg>
              <span>{{ emptyText }}</span>
            </div>
          </td>
        </tr>
        
        <!-- Data Rows -->
        <tr
          v-else
          v-for="(row, index) in data"
          :key="row[rowKey]"
          class="data-table__row"
          :class="{
            'data-table__row--selected': selectable && isSelected(row),
            'data-table__row--animate': animate
          }"
          :style="animate ? { animationDelay: `${getRowAnimationDelay(index)}ms` } : {}"
          @click="handleRowClick(row, $event)"
        >
          <!-- Selection Cell -->
          <td v-if="selectable" class="data-table__cell data-table__cell--checkbox" @click.stop>
            <label class="checkbox-wrapper">
              <input
                type="checkbox"
                :checked="isSelected(row)"
                @change="toggleSelection(row)"
                class="checkbox-input"
              />
              <span class="checkbox-custom"></span>
            </label>
          </td>
          
          <!-- Data Cells -->
          <td
            v-for="column in columns"
            :key="column.key"
            class="data-table__cell"
            :class="`data-table__cell--${column.align || 'left'}`"
          >
            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.data-table-wrapper {
  overflow-x: auto;
  border-radius: var(--radius-xl);
  border: 1px solid var(--border-light);
  background: var(--bg-primary);
}

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: var(--text-md);
}

/* Header */
.data-table__head {
  background: var(--bg-secondary);
}

.data-table__th {
  padding: var(--space-3) var(--space-4);
  font-weight: var(--font-semibold);
  color: var(--text-secondary);
  text-align: left;
  white-space: nowrap;
  border-bottom: 1px solid var(--border-light);
  transition: background-color var(--duration-fast) var(--ease-out-quart);
}

.data-table__th--sortable {
  cursor: pointer;
  user-select: none;
}

.data-table__th--sortable:hover {
  background: var(--color-gray-100);
  color: var(--text-primary);
}

.data-table__th--sorted {
  color: var(--color-primary-600);
  background: var(--color-primary-50);
}

.data-table__th-content {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
}

.sort-icon {
  display: inline-flex;
  opacity: 0.3;
  transition: all var(--duration-fast) var(--ease-out-quart);
}

.sort-icon svg {
  width: 1rem;
  height: 1rem;
  transition: transform var(--duration-fast) var(--ease-out-quart);
}

.sort-icon--active {
  opacity: 1;
  color: var(--color-primary-600);
}

.sort-icon--asc svg {
  transform: rotate(180deg);
}

/* Alignment */
.data-table__th--center,
.data-table__cell--center {
  text-align: center;
}

.data-table__th--right,
.data-table__cell--right {
  text-align: right;
}

/* Body */
.data-table__body {
  background: var(--bg-primary);
}

.data-table__row {
  transition: background-color var(--duration-fast) var(--ease-out-quart);
}

.data-table__row--animate {
  opacity: 0;
  animation: rowEnter var(--duration-normal) var(--ease-out-quart) forwards;
}

@keyframes rowEnter {
  from {
    opacity: 0;
    transform: translateX(-8px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.data-table--striped .data-table__row:nth-child(even) {
  background: var(--bg-secondary);
}

.data-table--hover .data-table__row:hover {
  background: var(--color-primary-50) !important;
}

.data-table__row--selected {
  background: var(--color-primary-50) !important;
}

/* Cells */
.data-table__cell {
  padding: var(--space-3) var(--space-4);
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-light);
  transition: color var(--duration-fast) var(--ease-out-quart);
}

.data-table__row:hover .data-table__cell {
  color: var(--text-primary);
}

.data-table__cell--checkbox,
.data-table__th--checkbox {
  width: 3rem;
  padding: var(--space-3);
  text-align: center;
}

/* Checkbox */
.checkbox-wrapper {
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  position: relative;
}

.checkbox-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.checkbox-custom {
  width: 1.125rem;
  height: 1.125rem;
  border: 2px solid var(--color-gray-300);
  border-radius: var(--radius-sm);
  background: var(--bg-primary);
  transition: all var(--duration-fast) var(--ease-out-quart);
  position: relative;
}

.checkbox-input:checked + .checkbox-custom {
  background: var(--color-primary-500);
  border-color: var(--color-primary-500);
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '';
  position: absolute;
  left: 4px;
  top: 1px;
  width: 5px;
  height: 9px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-input:focus-visible + .checkbox-custom {
  outline: 2px solid var(--color-primary-300);
  outline-offset: 2px;
}

/* Loading State */
.data-table__cell--loading {
  padding: var(--space-8);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  color: var(--text-muted);
}

.loading-spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-gray-200);
  border-top-color: var(--color-primary-500);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Empty State */
.data-table__cell--empty {
  padding: var(--space-10) var(--space-6);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  color: var(--text-muted);
}

.empty-icon {
  width: 3rem;
  height: 3rem;
  opacity: 0.5;
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .data-table__row--animate {
    animation: none;
    opacity: 1;
  }
  
  .loading-spinner {
    animation: none;
    border: 3px solid var(--color-gray-300);
  }
  
  .sort-icon svg,
  .checkbox-custom,
  .data-table__cell,
  .data-table__row,
  .data-table__th {
    transition: none;
  }
}
</style>
