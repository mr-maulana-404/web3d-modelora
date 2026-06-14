<div class="modal fade" id="modelDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content p-4" style="border-radius: 20px; overflow:hidden; background:#ECEEF2;">
            
            {{-- Close button --}}
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>

            <div class="modal-body px-2 px-md-3">

                <div class="row g-4 align-items-stretch">

                    {{-- LEFT (3/5) : Viewer --}}
                    <div class="col-12 col-lg-7">
                        <div id="viewerContainer"
                            style="
                                width:100%;
                                height:420px;
                                background:#d9d9d9;
                                border-radius:18px;
                                overflow:hidden;
                            ">
                        </div>
                    </div>

                    {{-- RIGHT (2/5) : Info + Buttons --}}
                    <div class="col-12 col-lg-5 d-flex flex-column">

                        {{-- Info --}}
                        <div>
                            <h4 class="fw-bold mb-1" id="modalModelName">
                                Model Name
                            </h4>

                            <small class="text-muted fw-bold d-block mb-3" id="modalModelCategory">
                                Category
                            </small>

                            {{-- Scrollable Description --}}
                            <div
                                id="modalModelDescription"
                                class="text-muted small"
                                style="
                                    max-height: 200px;
                                    overflow-y: auto;
                                    padding-right: 6px;
                                    white-space: pre-wrap;
                                "
                            >
                                Deskripsi model...
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-auto">
                            <div class="d-grid gap-2">
                                <div class="row g-2">
                                    <div class="col-12">
                                        @auth
                                        <a id="btnCustomize" class="btn btn-gradient-custom w-100 py-3">
                                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Customize
                                        </a>
                                        @endauth
                                    </div>
                                </div>

                                {{-- Admin/Owner Actions --}}
                                @auth
                                <div id="ownerActions" class="mt-2 pt-3 border-top" style="display:none">
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <form id="formDelete"  method="POST" class="w-100">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-soft-danger w-100 py-2">
                                                    <i class="fa-solid fa-trash me-1"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                        <div class="col-4">
                                            <a id="btnEditModel" class="btn btn-soft-warning w-100 py-2">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Data
                                            </a>
                                        </div>
                                        <div class="col-4">
                                            <form id="formPublish" method="POST" class="w-100">
                                                @csrf
                                                <button id="btnPublish" class="btn btn-soft-primary w-100 py-2">
                                                    <i class="fa-solid fa-paper-plane me-1"></i> Publish
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endauth
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
