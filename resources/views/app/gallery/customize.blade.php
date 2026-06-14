@extends('layouts.app')
@section('title', 'Customize Model')

@section('content')
<div class="customizePage">

    {{-- LEFT PANEL --}}
    <aside class="customizeLeft">
        <div class="customizeLeft__header">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="navbar-brand d-flex justify-content-center">
                <img src="{{ asset('storage/img/modelora_logo_light.png') }}" alt="MOD3LORA" height="32">
            </a>
        </div>

        <div class="customizeLeft__titlebox">
            <span class="subtitle">CUSTOMIZING</span>
            <h1 class="model-name">{{ $model3d->name }}</h1>
        </div>
        
        <div class="customizeLeft__toolbar">
            <button id="btnBack" class="btn-icon-text" title="Back">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <div class="toolbar-group">
                <button class="btn-icon-text outline" type="button" id="btnSetDefault" title="Reset Selected Part">
                    <i class="fas fa-undo"></i> Part
                </button>
                <button id="btnReset" class="btn-icon-text outline danger" title="Reset All Customizations">
                    <i class="fas fa-trash-restore"></i> All
                </button>
            </div>
        </div>

        <div class="customizeLeft__section flex-grow">
            <div class="sectionTitle">
                <i class="fas fa-cube"></i> Model Parts
            </div>
            <div class="partList custom-scrollbar" id="partsList">
                @foreach($parts as $part)
                    <button
                        type="button"
                        class="partBtn"
                        data-part-id="{{ $part->id }}"
                        data-mesh-name="{{ $part->mesh_name }}"
                    >
                        <span>{{ $part->part_name }}</span>
                        <i class="fas fa-chevron-right icon-indicator"></i>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="customizeLeft__section">
            <button class="btnAI" id="btnJumpToTextureSuggestions" type="button">
                <i class="fas fa-magic"></i> Generate Texture With AI
            </button>
        </div>

        <div class="customizeLeft__section tool-card">
            <div class="sectionTitle"><i class="fas fa-camera"></i> Camera & Capture</div>
            <div class="capture-controls">
                <div class="input-group-modern">
                    <label for="captureRatio">Ratio</label>
                    <div class="select-wrapper">
                        <select id="captureRatio">
                            <option value="1:1">1:1 (Square)</option>
                            <option value="16:9">16:9 (Landscape)</option>
                            <option value="4:5">4:5 (Portrait)</option>
                            <option value="9:16">9:16 (Story)</option>
                        </select>
                        <i class="fas fa-angle-down select-icon"></i>
                    </div>
                </div>

                <div class="capture-buttons">
                    <button id="btnCapture" class="btn-modern primary-soft">
                        <i class="fas fa-camera"></i> Capture
                    </button>
                    <button id="btnOpenGallery" class="btn-modern secondary" data-bs-toggle="modal" data-bs-target="#captureModal">
                        <i class="fas fa-images"></i> (<span id="captureCount">0</span>)
                    </button>
                </div>
            </div>    
        </div>

        <div class="customizeLeft__section mt-auto">
            <button class="btnSave" id="btnSaveCustomization" type="button">
                <i class="fas fa-save"></i> Save & Download
            </button>
        </div>
    </aside>

    {{-- CENTER VIEWER --}}
    <main class="customizeCenter">
        <div id="customizeViewer" class="viewerCanvas"></div>
    </main>

    {{-- RIGHT PANEL --}}
    <aside class="customizeRight">

        {{-- ADMIN TEXTURES --}}
        <div class="customizeRight__section flex-grow right-panel-section">
            <div class="sectionTitle"><i class="fas fa-paint-brush"></i> Material Library</div>

            @php
                $categories = $adminTextures->keys();
                $firstCategory = $categories->first();
            @endphp

            {{-- Tabs --}}
            <div class="textureTabs">
                @foreach($categories as $cat)
                    <button
                        type="button"
                        class="textureTabBtn {{ $cat === $firstCategory ? 'active' : '' }}"
                        data-tab="{{ \Illuminate\Support\Str::slug($cat) }}"
                    >
                        {{ $cat }}
                    </button>
                @endforeach
            </div>

            {{-- Tab Contents --}}
            <div class="textureTabContentWrapper">
                @foreach($adminTextures as $cat => $textures)
                    <div
                        class="textureTabContent custom-scrollbar {{ $cat === $firstCategory ? 'active' : '' }}"
                        id="tab-{{ \Illuminate\Support\Str::slug($cat) }}"
                    >
                        @forelse($textures as $tex)
                            <button
                                type="button"
                                class="textureRow admin-texture-btn"
                                data-texture-path="{{ $tex->texture_path }}"
                                title="{{ $tex->name }}"
                            >
                                <img
                                    class="textureThumb"
                                    src="{{ asset('storage/' . $tex->texture_path) }}"
                                    alt="{{ $tex->name }}"
                                >
                                <span class="textureLabel">{{ $tex->name }}</span>
                            </button>
                        @empty
                            <div class="empty-state">No textures available in this category.</div>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>

        {{-- AI TEXTURES --}}
        <div class="customizeRight__section tool-card right-panel-section aiPanel-hidden" id="textureSuggestionCard">
            <div class="sectionTitle"><i class="fas fa-wand-magic-sparkles"></i> AI Texture Generator</div>

            <div class="suggestionMetaRow">
                <div class="suggestionPartLabel">
                    Part:
                    <span id="textureSuggestionSelectedPart">Select a part first</span>
                </div>
                <div class="suggestionMetaBadges">
                    <span class="suggestionProviderBadge" id="textureSuggestionProviderBadge">Gemini</span>
                    <span class="suggestionCreditBadge">
                        <i class="fas fa-coins"></i>
                        @if($isCreditExempt ?? false)
                            Admin Free
                        @else
                            <span id="textureCreditBalance">{{ $creditBalance ?? 0 }}</span> credits
                        @endif
                    </span>
                </div>
            </div>

            <div class="input-group-stack">
                <label for="textureSuggestionPrompt">Prompt</label>
                <textarea
                    id="textureSuggestionPrompt"
                    rows="3"
                    placeholder="Contoh: buat texture woven navy premium untuk bagian ini, detail rapat, clean, dan cocok untuk jaket modern."
                ></textarea>
            </div>

            <div class="input-group-stack">
                <label for="textureSuggestionStyle">Style</label>
                <div class="select-wrapper">
                    <select id="textureSuggestionStyle">
                        <option value="auto">Auto Detect</option>
                        <option value="realistic">Realistic</option>
                        <option value="sport">Sporty</option>
                        <option value="luxury">Luxury</option>
                        <option value="natural">Natural</option>
                        <option value="playful">Playful</option>
                        <option value="minimal">Minimal</option>
                    </select>
                    <i class="fas fa-angle-down select-icon"></i>
                </div>
            </div>

            <button type="button" class="btn-modern primary-soft" id="btnTextureSuggestion">
                <i class="fas fa-wand-magic-sparkles"></i>
                Generate New Textures
                @if($isCreditExempt ?? false)
                    <span class="credit-cost">Free</span>
                @else
                    <span class="credit-cost">-{{ $textureSuggestionCost ?? 10 }} credits</span>
                @endif
            </button>

            <div class="suggestionSummary" id="textureSuggestionSummary">
                Gemini akan membuat texture baru dan otomatis menambahkannya ke My Textures.
            </div>

            <div class="userTextureList suggestionList custom-scrollbar" id="textureSuggestionList">
                <div class="empty-state">Belum ada texture AI. Pilih part lalu klik Generate New Textures.</div>
            </div>
        </div>

        {{-- Upload --}}
        <div class="customizeRight__section tool-card right-panel-section">
            <div class="sectionTitle"><i class="fas fa-cloud-upload-alt"></i> Custom Texture</div>
            
            <input type="file" id="userTextureInput" accept=".jpg, .jpeg, .png, .webp" hidden>
            <button type="button" class="btn-modern outline dashed" id="btnUploadTexture">
                <i class="fas fa-plus"></i> Upload Image
            </button>

            <div class="userTextureList custom-scrollbar" id="userTextureList">
            </div>
        </div>

        {{-- Plain Color --}}
        <div class="customizeRight__section tool-card right-panel-section">
            <div class="sectionTitle"><i class="fas fa-palette"></i> Plain Color</div>
            <div class="colorPickerBox">
                <input type="color" id="plainColorPicker" value="#ff0000">
                <span id="hexValue">#FF0000</span>
            </div>
        </div>

    </aside>

</div>

{{-- modal preview (Dark Theme Customization) --}}
<div class="modal fade dark-modal" id="captureModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-images"></i> Captured Images</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body custom-scrollbar">
            <div id="captureGallery" class="capture-gallery"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modern outline" data-bs-dismiss="modal">Close</button>
            <button id="btnDownloadSelected" class="btn-modern primary-soft">
                <i class="fas fa-download"></i> Download Selected
            </button>
        </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
    {{-- library js  --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    
    <script>
        // Data JS 
        window.MODEL_DATA = {
            id: {{ $model3d->id }},
            slug: @json($model3d->slug),
            model_path: @json($model3d->model_path),
        };
        window.MODEL_PARTS = @json($parts);
        window.ADMIN_TEXTURES = @json($adminTextures);
        window.USER_TEXTURES = @json($userTextures);
        window.STORAGE_URL = @json(asset('storage'));
        window.TEXTURE_SUGGESTION_ENDPOINT = @json(route('texture-suggestions.suggest'));
        window.CREDIT_DATA = {
            balance: @json($creditBalance ?? 0),
            cost: @json($textureSuggestionCost ?? 10),
            exempt: @json($isCreditExempt ?? false),
        };

        const resetBtn = document.getElementById("btnReset");
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.reload();
            });
        }

        @if(isset($savedCustomization))
            window.SAVED_CUSTOMIZATION = @json($savedCustomization->textures);
        @else
            window.SAVED_CUSTOMIZATION = null;
        @endif        
    </script>
    @vite('resources/js/pages/appcustomize.js')
@endpush
