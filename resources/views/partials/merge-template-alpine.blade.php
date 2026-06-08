{{-- Include inside parent x-data { ... } — provides merge template picker state & methods --}}
@php
    $mergeTemplateLibraryJson = ($templateAssets ?? collect())->map(function ($t) {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'path' => $t->file_path,
            'url' => $t->public_url,
        ];
    })->values();
@endphp
libraryTemplates: {{ \Illuminate\Support\Js::from($mergeTemplateLibraryJson) }},
templateDropdownId: '',
isSavingTemplate: false,

resetTemplateSelection() {
    this.templateFile = null;
    this.templatePreview = null;
    this.selectedTemplatePath = null;
    this.selectedTemplateId = null;
    this.templateDropdownId = '';
    this.templateUrl = '';
},

selectLibraryTemplate(asset) {
    this.templateFile = null;
    this.selectedTemplatePath = asset.path;
    this.selectedTemplateId = asset.id;
    this.templatePreview = asset.url;
    this.templateDropdownId = String(asset.id);
    if (typeof this.templateUrl !== 'undefined') {
        this.templateUrl = asset.url;
    }
},

onTemplateDropdownChange(event) {
    const id = event.target.value;
    this.templateDropdownId = id;
    if (!id) {
        this.templatePreview = null;
        this.selectedTemplateId = null;
        this.selectedTemplatePath = null;
        return;
    }
    const tpl = this.libraryTemplates.find(t => String(t.id) === String(id));
    if (tpl) {
        this.selectLibraryTemplate(tpl);
    }
},

async handleTemplateUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    event.target.value = '';

    this.isSavingTemplate = true;
    const formData = new FormData();
    formData.append('file_paths[]', file);
    formData.append('asset_type', 'template');
    formData.append('_token', '{{ csrf_token() }}');

    try {
        const response = await fetch('{{ route('assets.store') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok || !result.success || !result.assets?.length) {
            throw new Error(result.message || 'Could not save template to library.');
        }

        for (const asset of result.assets) {
            if (!this.libraryTemplates.find(t => String(t.id) === String(asset.id))) {
                this.libraryTemplates.push(asset);
            }
        }

        const saved = result.assets[result.assets.length - 1];
        this.selectLibraryTemplate(saved);
        $dispatch('notify', { message: 'Template saved to your library.', type: 'success' });
    } catch (e) {
        $dispatch('notify', { message: e.message || 'Upload failed.', type: 'error' });
    } finally {
        this.isSavingTemplate = false;
    }
},
