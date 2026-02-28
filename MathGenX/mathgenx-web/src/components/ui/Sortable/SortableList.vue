<template>
  <div class="flex flex-wrap space-y-5 lg:space-x-5 lg:space-y-0">
    <div
      v-for="list in lists"
      :key="list.id"
      class="flex-auto lg:flex-1 w-full lg:w-50"
    >
      <ul
        :ref="(el) => setListRef(el, list.id)"
        :id="list.id"
        class="flex flex-col"
        :class="listClass"
      >
        <li
          v-for="(item, index) in list.items"
          :key="getItemKey(item, index)"
          class="inline-flex items-center gap-x-3 py-3 px-4 cursor-grab text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200"
          :class="itemClass"
        >
          <slot
            name="item"
            :item="item"
            :index="index"
            :listId="list.id"
          >
            <!-- Default item rendering -->
            <span>{{ getItemLabel(item) }}</span>
          </slot>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, type ComponentPublicInstance } from 'vue'
import Sortable from 'sortablejs'
import type { SortableEvent } from 'sortablejs'

interface SortableItem {
  [key: string]: any
}

interface SortableListConfig {
  id: string
  items: SortableItem[]
}

interface Props {
  lists: SortableListConfig[]
  group?: string
  animation?: number
  dragClass?: string
  itemClass?: string
  listClass?: string
  itemKey?: string | ((item: SortableItem) => string | number)
  itemLabel?: string | ((item: SortableItem) => string)
  options?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  group: 'shared',
  animation: 150,
  dragClass: 'rounded-none!',
  itemClass: '',
  listClass: '',
  itemKey: 'id',
  itemLabel: 'label',
  options: () => ({}),
})

const emit = defineEmits<{
  'update:lists': [lists: SortableListConfig[]]
  change: [event: { from: string; to: string; item: SortableItem; oldIndex: number; newIndex: number }]
  add: [event: { to: string; item: SortableItem; newIndex: number }]
  remove: [event: { from: string; item: SortableItem; oldIndex: number }]
  update: [event: { listId: string; oldIndex: number; newIndex: number }]
}>()

const listRefs = ref<Record<string, HTMLElement | null>>({})
const sortableInstances = ref<Record<string, Sortable | null>>({})
const isUpdating = ref(false)
const dragState = ref<{
  fromListId: string
  fromIndex: number
  item: SortableItem | null
} | null>(null)

const setListRef = (el: Element | ComponentPublicInstance | null, listId: string) => {
  if (el && el instanceof HTMLElement) {
    listRefs.value[listId] = el
  }
}

const getItemKey = (item: SortableItem, index: number): string | number => {
  if (typeof props.itemKey === 'function') {
    return props.itemKey(item)
  }
  return item[props.itemKey] ?? index
}

const getItemLabel = (item: SortableItem): string => {
  if (typeof props.itemLabel === 'function') {
    return props.itemLabel(item)
  }
  return item[props.itemLabel] ?? String(item)
}

const initializeSortable = () => {
  // Destroy existing instances
  Object.values(sortableInstances.value).forEach((instance) => {
    if (instance) {
      instance.destroy()
    }
  })
  sortableInstances.value = {}

  // Create new instances
  props.lists.forEach((list) => {
    const element = listRefs.value[list.id]
    if (!element) return

    const options: Sortable.Options = {
      group: props.group,
      animation: props.animation,
      dragClass: props.dragClass,
      forceFallback: false,
      ...props.options,
      onStart: (evt: SortableEvent) => {
        // Store the original state when drag starts
        const fromListId = evt.from.id
        const oldIndex = evt.oldIndex ?? -1
        const fromList = props.lists.find((l) => l.id === fromListId)
        const item = fromList?.items[oldIndex]

        if (item) {
          dragState.value = {
            fromListId,
            fromIndex: oldIndex,
            item: JSON.parse(JSON.stringify(item)), // Deep copy
          }
        }

        // Call original onStart if provided
        if (props.options.onStart) {
          props.options.onStart(evt)
        }
      },
      onEnd: (evt: SortableEvent) => {
        const fromListId = evt.from.id
        const toListId = evt.to.id
        const oldIndex = evt.oldIndex ?? -1
        const newIndex = evt.newIndex ?? -1

        // Use stored drag state if available, otherwise fall back to current state
        const storedState = dragState.value
        const item = storedState?.item || props.lists.find((l) => l.id === fromListId)?.items[oldIndex]

        if (item) {
          // Set flag to prevent reinitialization during update
          isUpdating.value = true

          // Use stored item or create a deep copy
          const itemCopy = storedState?.item ? JSON.parse(JSON.stringify(storedState.item)) : JSON.parse(JSON.stringify(item))
          const actualOldIndex = storedState?.fromIndex ?? oldIndex

          // Create updated lists
          const updatedLists = props.lists.map((list) => ({ ...list, items: [...list.items] }))
          const fromList = updatedLists.find((l) => l.id === fromListId)
          const toList = updatedLists.find((l) => l.id === toListId)

          if (fromList && toList) {
            if (fromListId === toListId) {
              // Same list reorder
              const items = [...fromList.items]
              const [movedItem] = items.splice(actualOldIndex, 1)
              if (movedItem) {
                items.splice(newIndex, 0, movedItem)
                fromList.items = items
              }
            } else {
              // Different lists - move item from one list to another
              const fromItems = [...fromList.items]
              const fromItemIndex = fromItems.findIndex((i) => {
                const key = typeof props.itemKey === 'function' ? props.itemKey(i) : i[props.itemKey]
                const itemKey = typeof props.itemKey === 'function' ? props.itemKey(itemCopy) : itemCopy[props.itemKey]
                return key === itemKey
              })
              if (fromItemIndex !== -1) {
                fromItems.splice(fromItemIndex, 1)
                fromList.items = fromItems
              }

              // Add to target list (only if not already there)
              const toItems = [...toList.items]
              const itemExists = toItems.some((i) => {
                const key = typeof props.itemKey === 'function' ? props.itemKey(i) : i[props.itemKey]
                const itemKey = typeof props.itemKey === 'function' ? props.itemKey(itemCopy) : itemCopy[props.itemKey]
                return key === itemKey
              })
              if (!itemExists) {
                const safeIndex = Math.min(newIndex, toItems.length)
                toItems.splice(safeIndex, 0, itemCopy)
                toList.items = toItems
              }
            }

            // Emit updated lists to parent via v-model
            emit('update:lists', updatedLists)
          }

          // Emit change event for parent to listen if needed
          emit('change', {
            from: fromListId,
            to: toListId,
            item: itemCopy,
            oldIndex: actualOldIndex,
            newIndex,
          })

          // Emit specific events
          if (fromListId !== toListId) {
            emit('add', {
              to: toListId,
              item: itemCopy,
              newIndex,
            })
            emit('remove', {
              from: fromListId,
              item: itemCopy,
              oldIndex: actualOldIndex,
            })
          } else {
            // Same list reorder
            emit('update', {
              listId: fromListId,
              oldIndex: actualOldIndex,
              newIndex,
            })
          }

          // Clear drag state
          dragState.value = null

          // Reset flag after a short delay to allow parent to update
          setTimeout(() => {
            isUpdating.value = false
          }, 100)
        }

        // Call original onEnd if provided
        if (props.options.onEnd) {
          props.options.onEnd(evt)
        }
      },
    }

    sortableInstances.value[list.id] = new Sortable(element, options)
  })
}

onMounted(() => {
  // Wait for next tick to ensure DOM is ready
  setTimeout(() => {
    initializeSortable()
  }, 0)
})

onBeforeUnmount(() => {
  // Clean up all sortable instances
  Object.values(sortableInstances.value).forEach((instance) => {
    if (instance) {
      instance.destroy()
    }
  })
})

// Watch for changes in list structure (new lists added/removed) but not items
// This prevents reinitializing on every item change which causes conflicts
watch(
  () => props.lists.map((l) => l.id),
  () => {
    if (!isUpdating.value) {
      setTimeout(() => {
        initializeSortable()
      }, 0)
    }
  }
)
</script>

<style scoped>
/* Additional styles if needed */
</style>
