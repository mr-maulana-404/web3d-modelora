import * as THREE from "three";
import { OrbitControls } from "three/examples/jsm/controls/OrbitControls.js";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

let scene, camera, renderer, controls;
let gltfScene = null;

let raycaster = new THREE.Raycaster();
let mouse = new THREE.Vector2();

let selectedMesh = null;
let selectedPart = null;

let defaultMaterialState = {};

// key: model_part_id
let customizationState = {};

// cache TextureLoader biar tidak bikin object baru terus
const textureLoader = new THREE.TextureLoader();

// supaya kita bisa select mesh dari sidebar (meshName => meshObject)
let meshMap = {};

// INIT
function init() {
  const container = document.getElementById("customizeViewer");

  scene = new THREE.Scene();
  scene.background = new THREE.Color(0xdddddd);

  camera = new THREE.PerspectiveCamera(
    45,
    container.clientWidth / container.clientHeight,
    0.1,
    1000
  );
  camera.position.set(0, 1.2, 3);

  renderer = new THREE.WebGLRenderer({
    antialias: true,
    preserveDrawingBuffer: true // WAJIB untuk capture
  });
  renderer.setSize(container.clientWidth, container.clientHeight);
  renderer.setPixelRatio(window.devicePixelRatio);

  container.innerHTML = "";
  container.appendChild(renderer.domElement);

  controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;

  // light
  scene.add(new THREE.AmbientLight(0xffffff, 0.9));
  const dirLight = new THREE.DirectionalLight(0xffffff, 1);
  dirLight.position.set(3, 5, 2);
  scene.add(dirLight);

  // ground
  const ground = new THREE.Mesh(
    new THREE.CircleGeometry(20, 20),
    new THREE.MeshStandardMaterial({ color: 0xf2f2f2 })
  );
  ground.rotation.x = -Math.PI / 2;
  ground.position.y = -0.001;
  scene.add(ground);

  if (window.SAVED_CUSTOMIZATION) {
    window.SAVED_CUSTOMIZATION.forEach(item => {
      customizationState[item.model_part_id] = item;
    });
  }

  // load model
  loadModel(`/storage/${window.MODEL_DATA.model_path}`);

  // click mesh event
  renderer.domElement.addEventListener("click", onClickMesh);

  // sidebar part click
  document.querySelectorAll(".partBtn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const meshName = btn.getAttribute("data-mesh-name");
      const partId = btn.getAttribute("data-part-id");

      selectPartBySidebar(partId, meshName);
    });
  });

  // admin texture click
  document.querySelectorAll(".admin-texture-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const path = btn.getAttribute("data-texture-path");
      applyTextureToSelectedPart("admin", path);
    });
  });

  // upload texture button
  const btnUpload = document.getElementById("btnUploadTexture");
  const inputUpload = document.getElementById("userTextureInput");

  if (btnUpload && inputUpload) {
    btnUpload.addEventListener("click", () => inputUpload.click());

    inputUpload.addEventListener("change", async (e) => {
      if (!e.target.files || e.target.files.length === 0) return;
      await uploadUserTexture(e.target.files[0]);
      inputUpload.value = "";
    });
  }

  // plain color picker
  const plainColorPicker = document.getElementById("plainColorPicker");
  const hexValueDisplay = document.getElementById("hexValue");
  if (plainColorPicker) {
    plainColorPicker.addEventListener("input", (e) => {
      const color = e.target.value;
      // Update teks HEX
      if (hexValueDisplay) hexValueDisplay.textContent = color;
      // Apply ke model 3D
      applyTextureToSelectedPart("color", color);
    });
  }

  // save button
  const btnSave = document.getElementById("btnSaveCustomization");
  if (btnSave) {
    btnSave.addEventListener("click", saveCustomization);
  }

  // set default
  const btnDefault = document.getElementById("btnSetDefault");
  if (btnDefault) {
    btnDefault.addEventListener("click", resetSelectedPartToDefault);
  }

  // resize
  window.addEventListener("resize", () => resize(container));

  animate();
  initAdminTextureTabs();
  loadUserTextures();
  initAiTextureToggle();
  initTextureSuggestionControls();
}

function initAdminTextureTabs() {
  const tabButtons = document.querySelectorAll(".textureTabBtn");
  const tabContents = document.querySelectorAll(".textureTabContent");

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const tabId = btn.getAttribute("data-tab");

      tabButtons.forEach((b) => b.classList.remove("active"));
      tabContents.forEach((c) => c.classList.remove("active"));

      btn.classList.add("active");

      const target = document.getElementById(`tab-${tabId}`);
      if (target) target.classList.add("active");
    });
  });
}

// LOAD MODEL
function loadModel(url) {
  const loader = new GLTFLoader();

  loader.load(
    url,
    (gltf) => {
      gltfScene = gltf.scene;
      scene.add(gltfScene);

      buildMeshMap(gltfScene);
      fitCameraToObject(gltfScene);

      // APPLY SAVED TEXTURE DI SINI
      applySavedTextures();

      console.log("Model loaded. Mesh map:", meshMap);
    },
    undefined,
    (err) => {
      console.error("Gagal load model:", err);
      Swal.fire({
        icon: 'error',
        title: 'Data Not Found',
        text: 'Failed to load model',
        confirmButtonColor: '#0d6efd',
      });
    }
  );
}

function buildMeshMap(root) {
  meshMap = {};
  defaultMaterialState = {};

  root.traverse((child) => {
    if (!child.isMesh) return;

    meshMap[child.name] = child;

    // pastikan emissive ada
    if (child.material && child.material.isMeshStandardMaterial) {
      child.material.emissive = new THREE.Color(0x000000);
    }

    // simpan default state
    const mat = child.material;

    defaultMaterialState[child.name] = {
      map: mat?.map || null,
      color: mat?.color ? mat.color.clone() : new THREE.Color(0xffffff),
    };
  });
}

//apply saved texture
function applySavedTextures() {
  if (!window.SAVED_CUSTOMIZATION) return;

  gltfScene.traverse((child) => {
    if (!child.isMesh) return;

    const part = window.MODEL_PARTS.find(p => p.mesh_name === child.name);
    if (!part) return;

    const saved = customizationState[part.id];
    if (!saved) return;

    if (saved.texture_type === "color") {
      child.material.map = null;
      child.material.color = new THREE.Color(saved.color_value);
      child.material.needsUpdate = true;
    } else {
      const url = `/storage/${saved.texture_path}`;
      textureLoader.load(url, tex => {
        tex.flipY = false;
        tex.colorSpace = THREE.SRGBColorSpace;
        child.material.map = tex;
        child.material.color = new THREE.Color(0xffffff);
        child.material.needsUpdate = true;
      });
    }
  });
}


// CAMERA FIT
function fitCameraToObject(object, offset = 1.4) {
  const box = new THREE.Box3().setFromObject(object);
  const size = box.getSize(new THREE.Vector3());
  const center = box.getCenter(new THREE.Vector3());

  const maxDim = Math.max(size.x, size.y, size.z);
  const fov = camera.fov * (Math.PI / 180);
  let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2));

  cameraZ *= offset;

  camera.position.set(center.x, center.y + maxDim * 0.2, center.z + cameraZ);
  camera.lookAt(center);

  controls.target.copy(center);
  controls.update();
}

// FUNGSI UNSELECT
function clearSelection() {
  selectedMesh = null;
  selectedPart = null;

  // reset highlight emissive
  if (gltfScene) {
    gltfScene.traverse((child) => {
      if (child.isMesh && child.material && child.material.emissive) {
        child.material.emissive.setHex(0x000000);
      }
    });
  }

  // reset UI selected label
  const label = document.getElementById("selectedPartName");
  if (label) label.textContent = "-";

  updateTextureSuggestionSelectedPart();

  // reset active class part button
  document.querySelectorAll(".partBtn").forEach((btn) => {
    btn.classList.remove("active");
  });
}

// SELECT PART BY CLICK MESH
function onClickMesh(event) {
  if (!gltfScene) return;

  const rect = renderer.domElement.getBoundingClientRect();

  mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
  mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);

  const intersects = raycaster.intersectObjects(gltfScene.children, true);
  if (intersects.length === 0) {
    clearSelection();
    return;
  }

  const mesh = intersects[0].object;

  // cari part dari mesh.name
  const found = window.MODEL_PARTS.find((p) => p.mesh_name === mesh.name);

  if (!found) {
    // jangan spam alert terus, cukup console
    console.warn(`Mesh "${mesh.name}" tidak terdaftar di model_parts.`);
    return;
  }

  if (selectedMesh && selectedMesh.uuid === mesh.uuid) {
    clearSelection();
    return;
  }

  setSelectedPart(found, mesh);
}

// SELECT PART BY SIDEBAR
function selectPartBySidebar(partId, meshName) {
  if (!gltfScene) return;

  if (selectedPart && String(selectedPart.id) === String(partId)) {
    clearSelection();
    return;
  }

  const found = window.MODEL_PARTS.find((p) => String(p.id) === String(partId));

  if (!found) {
    Swal.fire({
        icon: 'error',
        title: 'Data Not Found',
        text: 'Part not found in MODEL_PARTS.',
        confirmButtonColor: '#0d6efd',
    });
    return;
  }

  const mesh = meshMap[meshName];

  if (!mesh) {
    Swal.fire({
        icon: 'error',
        title: 'Data Not Found',
        text: `Mesh "${meshName}" not found in GLTF.`,
        confirmButtonColor: '#0d6efd',
    });
    return;
  }

  setSelectedPart(found, mesh);
}

// SET SELECTED PART
function setSelectedPart(part, mesh) {
  selectedPart = part;
  selectedMesh = mesh;

  highlightSelectedMesh(mesh);
  updateSelectedPartUI(part);

  console.log("Selected part:", selectedPart);
}

function updateSelectedPartUI(part) {
  // highlight button part di sidebar kiri
  document.querySelectorAll(".partBtn").forEach((btn) => {
    btn.classList.remove("active");
    if (String(btn.dataset.partId) === String(part.id)) {
      btn.classList.add("active");
    }
  });

  updateTextureSuggestionSelectedPart(part);
}

// HIGHLIGHT
function highlightSelectedMesh(mesh) {
  if (!gltfScene) return;

  // reset semua highlight
  gltfScene.traverse((child) => {
    if (child.isMesh && child.material && child.material.emissive) {
      child.material.emissive.setHex(0x000000);
    }
  });

  // highlight mesh terpilih
  if (mesh.material && mesh.material.emissive) {
    mesh.material.emissive.setHex(0x3333ff);
  }
}

// APPLY TEXTURE
function applyTextureToSelectedPart(type, texturePathOrColor) {
  if (!selectedMesh || !selectedPart) {
    Swal.fire({
        icon: 'warning',
        title: 'Select Section',
        text: 'Please click on the model (mesh) section first.',
        confirmButtonColor: '#0d6efd', 
    });
    return;
  }

  // pastikan materialnya MeshStandardMaterial
  if (!selectedMesh.material || !selectedMesh.material.isMeshStandardMaterial) {
    selectedMesh.material = new THREE.MeshStandardMaterial({
      color: 0xffffff,
    });
  }

  // SOLID COLOR
  if (type === "color") {
    selectedMesh.material.map = null;
    selectedMesh.material.color = new THREE.Color(texturePathOrColor);
    selectedMesh.material.needsUpdate = true;

    customizationState[selectedPart.id] = {
      model_part_id: selectedPart.id,
      texture_type: "color",
      texture_path: null,
      color_value: texturePathOrColor,
    };

    return;
  }

  // ADMIN / USER TEXTURE
  const textureUrl = `/storage/${texturePathOrColor}`;

  textureLoader.load(
    textureUrl,
    (tex) => {
      // GLTF biasanya butuh flipY false
      tex.flipY = false;
      tex.colorSpace = THREE.SRGBColorSpace;

      selectedMesh.material.map = tex;
      selectedMesh.material.color = new THREE.Color(0xffffff);
      selectedMesh.material.needsUpdate = true;

      customizationState[selectedPart.id] = {
        model_part_id: selectedPart.id,
        texture_type: type,
        texture_path: texturePathOrColor,
        color_value: null,
      };
    },
    undefined,
    (err) => {
      console.error("Gagal load texture:", err);
      Swal.fire({
        icon: 'error',
        title: 'Data Not Found',
        text: 'Failed to load texture',
        confirmButtonColor: '#0d6efd',
      });
    }
  );
}

// RESET SELECTED PART TO DEFAULT
function resetSelectedPartToDefault() {
  if (!selectedMesh || !selectedPart) {
    Swal.fire({
        icon: 'warning',
        title: 'Select Section',
        text: 'Please click on the model (mesh) section first.',
        confirmButtonColor: '#0d6efd', // Warna Primary Bootstrap
    });
    return;
  }

  const meshName = selectedMesh.name;
  const defaultState = defaultMaterialState[meshName];

  if (!defaultState) {
    Swal.fire({
        icon: 'error',
        title: 'Data Not Found',
        text: 'Default material not found for this mesh.',
        confirmButtonColor: '#0d6efd',
    });
    return;
  }

  Swal.fire({
      title: 'Confirm Reset',
      text: `Are you sure you want to restore the part "${selectedPart.part_name}" to its original state? Current changes will be lost.`,
      icon: 'question',
      showCancelButton: true,  
      cancelButtonColor: '#6c757d', 
      confirmButtonColor: '#d84146', 
      cancelButtonText: 'Batal',
      confirmButtonText: 'Reset!'
  }).then((result) => {
      // Jika user menekan tombol "Reset!"
      if (result.isConfirmed) {
            
          // Jalankan logika reset
          selectedMesh.material.map = defaultState.map;
          selectedMesh.material.color.copy(defaultState.color);
          selectedMesh.material.needsUpdate = true;

          // Hapus dari state kustomisasi
          delete customizationState[selectedPart.id];

          // Tampilkan notifikasi sukses singkat (tanpa tombol OK)
          Swal.fire({
              icon: 'success',
              title: 'Reset successfully!',
              text: `Part "${selectedPart.part_name}" has been reverted to default.`,
              timer: 1500,
              showConfirmButton: false
          });
      }
  });
}

// CRUD USER TEXTURE
function csrfToken() {
  return document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");
}

async function uploadUserTexture(file) {
  const formData = new FormData();
  formData.append("texture", file);
  formData.append("name", file.name);

  try {
    const res = await fetch("/user-textures", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": csrfToken(),
      },
      body: formData,
    });

    const data = await res.json();

    if (!data.success) {
      Swal.fire({
        icon: 'error',
        title: 'Upload Failed',
        text: data.message || "An error occurred while uploading the texture.",
        confirmButtonColor: '#0d6efd', 
      });
      return;
    }

    // reload list biar masuk UI
    await loadUserTextures();

    // langsung apply ke selected part
    applyTextureToSelectedPart("user", data.texture.texture_path);

    Swal.fire({
        icon: 'success',
        title: 'Upload successfully!',
        text: 'Texture uploaded successfully!',
        timer: 1500,
        showConfirmButton: false
    });
  } catch (err) {
    console.error(err);
    Swal.fire({
        icon: 'error',
        title: 'An error occurred while uploading.',
        text: 'Make sure the image format is correct or check your internet connection.',
        confirmButtonColor: '#0d6efd',
    });
  }
}

async function loadUserTextures() {
  try {
    const res = await fetch("/user-textures");
    const data = await res.json();

    if (!data.success) return;

    renderUserTextureList(data.textures);
  } catch (err) {
    console.error("Gagal load user textures:", err);
  }
}

function initAiTextureToggle() {
  const toggleButton = document.getElementById("btnJumpToTextureSuggestions");
  const suggestionCard = document.getElementById("textureSuggestionCard");
  const defaultSections = document.querySelectorAll(
    ".right-panel-section:not(#textureSuggestionCard)"
  );

  if (!toggleButton || !suggestionCard) return;

  const setAiMode = (isActive) => {
    defaultSections.forEach((section) => {
      section.classList.toggle("aiPanel-hidden", isActive);
    });

    suggestionCard.classList.toggle("aiPanel-hidden", !isActive);
    toggleButton.classList.toggle("is-active", isActive);
    toggleButton.setAttribute("aria-expanded", isActive ? "true" : "false");
    toggleButton.innerHTML = isActive
      ? `<i class="fas fa-arrow-left"></i> Back to Default`
      : `<i class="fas fa-magic"></i> Generate Texture With AI`;

    if (isActive) {
      updateTextureSuggestionSelectedPart();
    }
  };

  setAiMode(false);

  toggleButton.addEventListener("click", () => {
    const isActive = toggleButton.classList.contains("is-active");
    setAiMode(!isActive);
  });
}

function initTextureSuggestionControls() {
  const btnSuggestion = document.getElementById("btnTextureSuggestion");

  if (btnSuggestion) {
    btnSuggestion.addEventListener("click", requestTextureSuggestions);
  }

  updateTextureSuggestionSelectedPart();
}

function updateTextureSuggestionSelectedPart(part = selectedPart) {
  const target = document.getElementById("textureSuggestionSelectedPart");

  if (!target) return;

  if (!part) {
    target.textContent = "Select a part first";
    return;
  }

  target.textContent = `${part.part_name} (${part.mesh_name})`;
}

function syncTextureCreditBadge(credits) {
  if (!credits || credits.exempt) return;

  const balanceEl = document.getElementById("textureCreditBalance");

  if (balanceEl && typeof credits.balance !== "undefined") {
    balanceEl.textContent = credits.balance;
  }

  if (window.CREDIT_DATA) {
    window.CREDIT_DATA.balance = credits.balance;
  }
}

async function requestTextureSuggestions() {
  if (!selectedPart) {
    Swal.fire({
      icon: "warning",
      title: "Select Section",
      text: "Please click on the model part first before generating new textures.",
      confirmButtonColor: "#0d6efd",
    });
    return;
  }

  const button = document.getElementById("btnTextureSuggestion");
  const promptInput = document.getElementById("textureSuggestionPrompt");
  const styleSelect = document.getElementById("textureSuggestionStyle");
  const summaryEl = document.getElementById("textureSuggestionSummary");

  const originalHtml = button ? button.innerHTML : "";

  if (button) {
    button.disabled = true;
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Generating...`;
  }

  if (summaryEl) {
    summaryEl.textContent = "Gemini sedang membuat texture baru untuk part yang dipilih...";
  }

  try {
    let screenshot = null;

    try {
      screenshot = captureSuggestionContext();
    } catch (captureError) {
      console.warn("Unable to capture texture generation context:", captureError);
    }

    const res = await fetch(window.TEXTURE_SUGGESTION_ENDPOINT || "/texture-suggestions", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken(),
      },
      body: JSON.stringify({
        model3d_id: window.MODEL_DATA.id,
        model_part_id: selectedPart.id,
        prompt: promptInput ? promptInput.value : "",
        style: styleSelect ? styleSelect.value : "auto",
        limit: 3,
        screenshot,
      }),
    });

    const responseText = await res.text();
    let data = null;

    try {
      data = responseText ? JSON.parse(responseText) : null;
    } catch (parseError) {
      throw new Error("Server returned a non-JSON response while generating textures. Check Laravel log for details.");
    }

    if (data?.credits) {
      syncTextureCreditBadge(data.credits);
    }

    if (!data || !res.ok || !data.success) {
      throw new Error(data.message || "Failed to generate new textures.");
    }

    await loadUserTextures();
    renderGeneratedTextureSuggestionList(data);
  } catch (err) {
    console.error("Texture generation error:", err);
    renderGeneratedTextureSuggestionList({
      provider: {
        name: "Unavailable",
        mode: "error",
      },
      summary: err.message || "Texture generation failed.",
      suggestions: [],
    });

    Swal.fire({
      icon: "error",
      title: "Generation Failed",
      text: err.message || "Texture generation request failed.",
      confirmButtonColor: "#0d6efd",
    });
  } finally {
    if (button) {
      button.disabled = false;
      button.innerHTML = originalHtml;
    }
  }
}

function renderTextureSuggestionList(payload) {
  const list = document.getElementById("textureSuggestionList");
  const summaryEl = document.getElementById("textureSuggestionSummary");
  const badgeEl = document.getElementById("textureSuggestionProviderBadge");

  if (summaryEl) {
    summaryEl.textContent = payload.summary || "No summary available.";
  }

  if (badgeEl) {
    const providerName = payload.provider?.name || "Unknown";
    const providerMode = payload.provider?.mode || "unknown";

    badgeEl.textContent = providerMode === "fallback_local"
      ? `${providerName} Fallback`
      : providerName;
  }

  if (!list) return;

  list.innerHTML = "";

  if (!payload.suggestions || payload.suggestions.length === 0) {
    list.innerHTML = `<div class="empty-state">Belum ada suggestion yang bisa dipakai.</div>`;
    return;
  }

  payload.suggestions.forEach((item) => {
    const row = document.createElement("div");
    row.className = "suggestionItem";
    row.innerHTML = `
      <div class="suggestionItem__header">
        <img class="userTextureThumb" src="${item.preview_url}" alt="${item.name}">
        <div class="suggestionItem__info">
          <div class="suggestionItem__name">${item.name}</div>
          <div class="suggestionItem__meta">${item.library} • ${item.category} • score ${Number(item.score || 0).toFixed(2)}</div>
        </div>
      </div>
      <div class="suggestionItem__reason">${item.reason || "Suggested by AI."}</div>
      <div class="userTextureActions">
        <button class="btnMini apply" type="button">Apply</button>
      </div>
    `;

    row.querySelector(".apply").addEventListener("click", () => {
      applyTextureToSelectedPart(item.texture_type, item.texture_path);
    });

    list.appendChild(row);
  });
}

function renderGeneratedTextureSuggestionList(payload) {
  const list = document.getElementById("textureSuggestionList");
  const summaryEl = document.getElementById("textureSuggestionSummary");
  const badgeEl = document.getElementById("textureSuggestionProviderBadge");

  if (summaryEl) {
    summaryEl.textContent = payload.summary || "No summary available.";
  }

  if (badgeEl) {
    const providerName = payload.provider?.name || "Unknown";
    const providerMode = payload.provider?.mode || "unknown";

    badgeEl.textContent = providerMode === "gemini_generated"
      ? `${providerName} Generated`
      : providerName;
  }

  if (!list) return;

  list.innerHTML = "";

  if (!payload.suggestions || payload.suggestions.length === 0) {
    list.innerHTML = `<div class="empty-state">Belum ada texture AI yang berhasil dibuat.</div>`;
    return;
  }

  payload.suggestions.forEach((item) => {
    const metaSegments = [item.library, item.category].filter(Boolean);
    const row = document.createElement("div");
    row.className = "suggestionItem";
    row.innerHTML = `
      <div class="suggestionItem__header">
        <img class="userTextureThumb" src="${item.preview_url}" alt="${item.name}">
        <div class="suggestionItem__info">
          <div class="suggestionItem__name">${item.name}</div>
          <div class="suggestionItem__meta">${metaSegments.join(" - ")}</div>
        </div>
      </div>
      <div class="suggestionItem__reason">${item.reason || "Generated by AI."}</div>
      <div class="userTextureActions">
        <button class="btnMini apply" type="button">Apply</button>
      </div>
    `;

    row.querySelector(".apply").addEventListener("click", () => {
      applyTextureToSelectedPart(item.texture_type, item.texture_path);
    });

    list.appendChild(row);
  });
}

function renderUserTextureList(textures) {
  const list = document.getElementById("userTextureList");
  if (!list) return;

  list.innerHTML = "";

  textures.forEach((tex) => {
    const row = document.createElement("div");
    row.className = "userTextureItem";
    row.dataset.textureId = tex.id;

    row.innerHTML = `
      <img class="userTextureThumb" src="${window.STORAGE_URL}/${tex.texture_path}" alt="${tex.name}">
      <div class="userTextureName">${tex.name}</div>
      <div class="userTextureActions">
        <button class="btnMini apply">Apply</button>
        <button class="btnMini delete">Delete</button>
      </div>
    `;

    // apply
    row.querySelector(".apply").addEventListener("click", () => {
      applyTextureToSelectedPart("user", tex.texture_path);
    });

    // delete
    row.querySelector(".delete").addEventListener("click", async () => {
      await deleteUserTexture(tex.id, row);
    });

    list.appendChild(row);
  });
}

async function deleteUserTexture(id, rowEl) {
  if (!confirm("Hapus texture ini?")) return;

  try {
    const res = await fetch(`/user-textures/${id}`, {
      method: "DELETE",
      headers: {
        "X-CSRF-TOKEN": csrfToken(),
      },
    });

    const data = await res.json();

    if (!data.success) {
      Swal.fire({
        icon: 'error',
        title: 'Failed to delete',
        text: data.message || "An error occurred while deleting the texture.",
        confirmButtonColor: '#0d6efd', 
      });
      return;
    }

    // remove dari UI
    if (rowEl) rowEl.remove();
  } catch (err) {
    console.error(err);
    Swal.fire({
      icon: 'error',
      title: 'Failed to delete',
      text: data.message || "An error occurred while deleting the texture.",
      confirmButtonColor: '#0d6efd', 
    });
  }
}

// BACK BUTTON
const btnBack = document.getElementById("btnBack");

if (btnBack) {
    btnBack.addEventListener("click", function (e) {
        e.preventDefault();

        Swal.fire({
            title: "Are you sure?",
            text: "All changes you made will not be saved.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, go back",
            cancelButtonText: "Stay here",
        }).then((result) => {
            if (result.isConfirmed) {
                window.history.back();
            }
        });
    });
}

// CAPTURE WIEWPORT MODEL
function captureThumbnail() {
  renderer.render(scene, camera); 
  return renderer.domElement.toDataURL("image/png");
}

function captureSuggestionContext() {
  renderer.render(scene, camera);
  return renderer.domElement.toDataURL("image/jpeg", 0.82);
}

// SAVE CUSTOMIZATION
async function saveCustomization() {
  Swal.fire({
    title: 'Save Customizations',
    input: 'text',
    inputLabel: 'Name of your customization?',
    inputValue: 'My Customization',
    showCancelButton: true,
    confirmButtonColor: '#0d6efd',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Save',
    cancelButtonText: 'Cancel',
    inputValidator: (value) => {
      if (!value) {
        return 'Name cannot be blank!';
      }
    }
  }).then(async (result) => {
    if (!result.isConfirmed) return;

    const name = result.value;

    if (Object.keys(customizationState).length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Changes',
        text: 'No changes have been saved yet.',
        confirmButtonColor: '#0d6efd'
      });
      return;
    }

    // Tampilkan loading sebentar karena proses capture & upload butuh waktu
    Swal.fire({
      title: 'Currently saving...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const thumbnailBase64 = captureThumbnail();

    const payload = {
      model3d_id: window.MODEL_DATA.id,
      name: name,
      textures: Object.values(customizationState),
      thumbnail: thumbnailBase64,
    };

    try {
      const res = await fetch("/customizations/save", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (!data.success) {
        Swal.fire({
          icon: 'error',
          title: 'Failed to Save',
          text: data.message || "Failed to Save",
          confirmButtonColor: '#0d6efd'
        });
        return;
      }

      // Berhasil
      await Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Customization successfully saved!',
        showConfirmButton: false,
        timer: 1500
      });

      window.location.href = "/gallery/saved-models";
    } catch (err) {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred while saving customization.',
        confirmButtonColor: '#0d6efd'
      });
    }
  });
}

// CAPTURE SYSTEM
let captureGallery;
document.addEventListener("DOMContentLoaded", () => {
    captureGallery = document.getElementById("captureGallery");
});
const btnCapture = document.getElementById("btnCapture");
const btnDownload = document.getElementById("btnDownloadSelected");

let capturedImages = [];

// Fungsi untuk mengambil gambar menggunakan renderer terpisah
function captureImage(width, height) {
    const captureRenderer = new THREE.WebGLRenderer({
        antialias: true,
        preserveDrawingBuffer: true,
        alpha: true 
    });

    captureRenderer.setSize(width, height);
    
    // logika kamera
    const originalAspect = camera.aspect;
    
    camera.aspect = width / height;
    camera.updateProjectionMatrix(); 

    captureRenderer.render(scene, camera);

    camera.aspect = originalAspect;
    camera.updateProjectionMatrix();

    const dataURL = captureRenderer.domElement.toDataURL("image/png");

    captureRenderer.dispose();

    return dataURL;
}

// Efek visual flash putih saat capture dilakukan
function captureAnimation() {
    const flash = document.createElement("div");

    // Styling flash element
    flash.style.position = "fixed";
    flash.style.inset = "0";
    flash.style.background = "white";
    flash.style.opacity = "0";
    flash.style.transition = "opacity .15s";
    flash.style.zIndex = "9999";
    flash.style.pointerEvents = "none"; // Agar user tetap bisa klik elemen di bawahnya

    document.body.appendChild(flash);

    // Trigger animasi masuk
    requestAnimationFrame(() => {
        flash.style.opacity = "0.8";
    });

    // Fade out dan hapus element
    setTimeout(() => {
        flash.style.opacity = "0";
        setTimeout(() => flash.remove(), 150);
    }, 120);
}

// Menambahkan element preview ke dalam gallery UI
function addPreview(dataURL){

    const item = document.createElement("div");
    item.className = "capture-item";

    item.innerHTML = `
        <input type="checkbox" checked>
        <img src="${dataURL}" style="width: 100%; height: 100%; object-fit: contain; border-radius: 4px;">
    `;

    captureGallery.appendChild(item);

    document.getElementById("captureCount").innerText =
        captureGallery.children.length;
}

// Handler Tombol Capture
btnCapture.addEventListener("click", () => {
    if (typeof renderer === 'undefined' || !renderer) return;

    const ratio = document.getElementById("captureRatio").value;

    // Default 1:1
    let width = 1024;
    let height = 1024;

    // Logika Penentuan Resolusi berdasarkan Rasio
    if (ratio === "16:9") {
        width = 1280;
        height = 720;
    } else if (ratio === "4:5") {
        width = 800;
        height = 1000;
    } else if (ratio === "9:16") {
        width = 720;
        height = 1280;
    }

    // Eksekusi Capture & Animasi
    const image = captureImage(width, height);
    captureAnimation();

    // Simpan ke array dan tampilkan di UI
    capturedImages.push(image);
    addPreview(image);
});

// Handler Tombol Download ZIP
btnDownload.addEventListener("click", async () => {
    const zip = new JSZip();
    const items = document.querySelectorAll(".capture-item");

    if (items.length === 0) {
        alert("Belum ada gambar yang diambil.");
        return;
    }

    let index = 1;

    items.forEach((item) => {
        const checkbox = item.querySelector("input");

        if (checkbox.checked) {
            const img = item.querySelector("img");
            // Mengambil data base64 setelah koma (menghapus data:image/png;base64,)
            const base64 = img.src.split(",")[1];

            zip.file(`preview_${index}.png`, base64, { base64: true });
            index++;
        } else {
            // Hapus preview dari UI jika tidak dipilih saat download (sesuai logika lama Anda)
            item.remove();
        }
    });

    // Proses pembuatan ZIP secara asinkron
    try {
        const blob = await zip.generateAsync({ type: "blob" });
        saveAs(blob, "model_capture.zip");
    } catch (error) {
        console.error("Gagal membuat file ZIP:", error);
    }
});

// RESIZE + ANIMATE
function resize(container) {
  if (!renderer || !camera) return;

  const width = container.clientWidth;
  const height = container.clientHeight;

  renderer.setSize(width, height);
  camera.aspect = width / height;
  camera.updateProjectionMatrix();
}

function animate() {
  requestAnimationFrame(animate);

  if (controls) controls.update();
  renderer.render(scene, camera);
}

document.addEventListener("DOMContentLoaded", init);
