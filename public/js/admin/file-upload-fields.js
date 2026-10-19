// Per-file text fields inside FilePond items.
// Server side: App\Filament\Forms\Components\FileUploadWithFields

// A plain object (not an array, not null). PHP sends an empty array as [] in JSON,
// and string keys put on a JS array are silently lost when serialized back.
const isPlainObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value)

document.addEventListener('alpine:init', () => {
    Alpine.data('fileUploadFields', ({ id, fieldsPath, fields, isDisabled }) => ({
        root: null,

        // { uuid: { alt: { uk: '...', en: '...' } } }
        values: {},

        init() {
            // The native .fi-fo-file-upload element rendered by Filament (same id as the field)
            this.root = document.getElementById(id)

            if (!this.root) {
                return
            }

            // Snapshot of the sibling key from the current Livewire state (a copy, not a proxy)
            const initial = this.$wire.$get(fieldsPath)
            this.values = isPlainObject(initial) ? JSON.parse(JSON.stringify(initial)) : {}

            // Several triggers can fire within one frame, so collapse them into one pass
            let isScheduled = false
            const refresh = () => {
                if (isScheduled) {
                    return
                }

                isScheduled = true

                requestAnimationFrame(() => {
                    isScheduled = false
                    this.injectAll()
                })
            }

            // FilePond (re)creates item elements: initial load, new files, rebuild after save
            new MutationObserver(refresh).observe(this.root, { childList: true, subtree: true })

            // An upload finished: serverId (= Filament uuid) is known now, but the DOM may not change
            this.root.addEventListener('FilePond:processfile', refresh)
        },

        injectAll() {
            // Filament's own Alpine component lives on the same element.
            // We read two things from it: the FilePond instance and its url → uuid index.
            const upload = Alpine.$data(this.root)
            const pond = upload?.pond

            // Filament's component is lazy-loaded (x-load); the observer will call us again
            if (!pond) {
                return
            }

            for (const file of pond.getFiles()) {
                const item = this.root.querySelector(`#filepond--item-${file.id}`)

                if (!item) {
                    continue
                }

                // Same rule Filament uses in its own 'reorderfiles' handler:
                // a new upload carries the uuid in serverId, an existing file is looked up by its URL
                const uuid = file.source instanceof File
                    ? file.serverId
                    : (upload.uploadedFileIndex?.[file.source] ?? null)

                // Still uploading: the fields will appear after 'processfile'
                if (!uuid) {
                    continue
                }

                // Already rendered for this file
                if (item.dataset.uploadFieldsUuid === uuid) {
                    continue
                }

                item.querySelector(':scope > .upload-fields__list')?.remove()
                item.dataset.uploadFieldsUuid = uuid
                item.append(this.buildList(uuid))
            }
        },

        buildList(uuid) {
            const list = document.createElement('div')
            list.className = 'upload-fields__list'

            for (const [path, label] of Object.entries(fields)) {
                const row = document.createElement('label')
                row.className = 'upload-fields__row'

                const caption = document.createElement('span')
                caption.className = 'upload-fields__label'
                caption.textContent = label

                const input = document.createElement('input')
                input.type = 'text'
                input.className = 'upload-fields__input'
                input.disabled = isDisabled
                input.value = path.split('.').reduce((node, key) => node?.[key], this.values[uuid]) ?? ''

                input.addEventListener('input', () => this.write(uuid, path, input.value))

                // Keep FilePond from treating clicks inside the input as an item drag or a browse click
                for (const type of ['pointerdown', 'mousedown', 'touchstart', 'click']) {
                    input.addEventListener(type, (event) => event.stopPropagation())
                }

                row.append(caption, input)
                list.append(row)
            }

            return list
        },

        // path is dot notation: 'alt.uk' → values[uuid].alt.uk
        write(uuid, path, value) {
            const keys = path.split('.')
            const lastKey = keys.pop()

            if (!isPlainObject(this.values[uuid])) {
                this.values[uuid] = {}
            }

            let node = this.values[uuid]

            for (const key of keys) {
                if (!isPlainObject(node[key])) {
                    node[key] = {}
                }

                node = node[key]
            }

            node[lastKey] = value

            // live = false: no request now, the value travels with the next Livewire request (e.g. Save)
            this.$wire.$set(`${fieldsPath}.${uuid}`, this.values[uuid], false)
        },
    }))
})