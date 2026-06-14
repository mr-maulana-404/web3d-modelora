import * as THREE from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";
import { GLTFExporter } from "three/examples/jsm/exporters/GLTFExporter.js";
import { STLExporter } from "three/examples/jsm/exporters/STLExporter.js";

const textureLoader = new THREE.TextureLoader();
textureLoader.setCrossOrigin("anonymous");

document.addEventListener("click", async (event) => {
  const button = event.target.closest(".btn-download-customized");
  if (!button) return;

  event.preventDefault();

  const payload = parsePayload(button);
  if (!payload) {
    showError("Data customization untuk download tidak valid.");
    return;
  }

  const format = await askDownloadFormat();
  if (!format) return;

  await runDownload(payload, format);
});

function parsePayload(button) {
  try {
    return JSON.parse(button.dataset.downloadCustomization || "{}");
  } catch (error) {
    console.error("Failed to parse download payload", error);
    return null;
  }
}

async function askDownloadFormat() {
  const result = await Swal.fire({
    title: "Download Customized Model",
    html: `
      <div class="text-start">
        <p class="small text-muted mb-3">Pilih format file hasil customisasi yang ingin didownload.</p>
        <div class="d-grid gap-2">
          ${formatOption("glb", "GLB", "Satu file binary, paling aman untuk texture dan material.")}
          ${formatOption("gltf", "GLTF", "File JSON glTF dengan texture tertanam sebagai data.")}
          ${formatOption("obj", "OBJ", "ZIP berisi OBJ, MTL, dan baked texture dari Blender.")}
          ${formatOption("stl", "STL", "Hanya geometry; format STL memang tidak menyimpan texture.")}
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Download",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#f7ba2c",
    preConfirm: () => {
      const selected = document.querySelector("input[name='downloadFormat']:checked");
      if (!selected) {
        Swal.showValidationMessage("Pilih salah satu format terlebih dahulu.");
        return false;
      }

      return selected.value;
    },
  });

  return result.isConfirmed ? result.value : null;
}

function formatOption(value, label, description) {
  return `
    <label class="border rounded-3 p-3 d-flex gap-3 align-items-start" style="cursor:pointer;">
      <input class="form-check-input mt-1" type="radio" name="downloadFormat" value="${value}" ${value === "glb" ? "checked" : ""}>
      <span>
        <span class="fw-bold d-block">${label}</span>
        <span class="small text-muted">${description}</span>
      </span>
    </label>
  `;
}

async function runDownload(payload, format) {
  if (!payload.model_path) {
    showError("Model asli tidak ditemukan untuk customization ini.");
    return;
  }

  Swal.fire({
    title: "Preparing download...",
    text: format === "obj"
      ? "Sistem sedang bake texture custom sebelum export OBJ."
      : "Model sedang dimuat dan texture custom sedang diterapkan.",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  try {
    const baseName = safeFileName(payload.name || payload.model_name || "customized-model");

    if (format === "obj") {
      await downloadServerBakedObj(payload, `${baseName}-obj-baked.zip`);
      Swal.close();
      return;
    }

    const root = await buildCustomizedScene(payload);

    if (format === "glb") {
      const data = await exportGltf(root, { binary: true });
      downloadBlob(new Blob([data], { type: "model/gltf-binary" }), `${baseName}.glb`);
    }

    if (format === "gltf") {
      const data = await exportGltf(root, { binary: false });
      const json = typeof data === "string" ? data : JSON.stringify(data, null, 2);
      downloadBlob(new Blob([json], { type: "model/gltf+json" }), `${baseName}.gltf`);
    }

    if (format === "stl") {
      const exporter = new STLExporter();
      const data = exporter.parse(root, { binary: true });
      downloadBlob(new Blob([data], { type: "model/stl" }), `${baseName}.stl`);
    }

    Swal.close();
  } catch (error) {
    console.error("Customized download failed", error);
    showError(error.message || "Gagal membuat file download.");
  }
}

async function buildCustomizedScene(payload) {
  const gltf = await loadGltf(`/storage/${payload.model_path}`);
  const root = gltf.scene;

  assignExportMaterialNames(root);
  await applySavedTextures(root, payload);

  root.updateMatrixWorld(true);
  return root;
}

function loadGltf(url) {
  return new Promise((resolve, reject) => {
    new GLTFLoader().load(url, resolve, undefined, reject);
  });
}

async function applySavedTextures(root, payload) {
  const texturesByMesh = mapTexturesByMesh(payload);
  const tasks = [];

  root.traverse((child) => {
    if (!child.isMesh) return;

    const saved = texturesByMesh.get(child.name);
    const material = exportableMaterial(child.material, child.name);

    if (!saved) {
      child.material = material;
      return;
    }

    if (saved.texture_type === "color") {
      material.map = null;
      material.color = new THREE.Color(saved.color_value || "#ffffff");
      material.needsUpdate = true;
      child.material = material;
      return;
    }

    if (saved.texture_path) {
      tasks.push(
        loadTexture(`/storage/${saved.texture_path}`).then((texture) => {
          material.map = texture;
          material.color = new THREE.Color(0xffffff);
          material.userData.downloadTextureUrl = `/storage/${saved.texture_path}`;
          material.needsUpdate = true;
          child.material = material;
        })
      );
    }
  });

  await Promise.all(tasks);
}

function mapTexturesByMesh(payload) {
  const partsById = new Map((payload.parts || []).map((part) => [String(part.id), part]));
  const result = new Map();

  (payload.textures || []).forEach((texture) => {
    const part = texture.mesh_name ? texture : partsById.get(String(texture.model_part_id));
    const meshName = texture.mesh_name || part?.mesh_name;

    if (meshName) {
      result.set(meshName, texture);
    }
  });

  return result;
}

function exportableMaterial(sourceMaterial, meshName) {
  const source = Array.isArray(sourceMaterial) ? sourceMaterial[0] : sourceMaterial;
  const canCloneForExport = source?.isMeshStandardMaterial || source?.isMeshPhysicalMaterial || source?.isMeshBasicMaterial;
  const material = canCloneForExport
    ? source.clone()
    : new THREE.MeshStandardMaterial({
        color: source?.color ? source.color.clone() : new THREE.Color(0xffffff),
        map: source?.map || null,
      });

  if (!material.color) {
    material.color = new THREE.Color(0xffffff);
  }

  material.name = material.name || `${safeFileName(meshName)}_material`;
  material.userData = { ...(material.userData || {}) };

  const sourceMap = material.map;
  const image = sourceMap?.image;
  const imageUrl = image?.currentSrc || image?.src;

  if (imageUrl) {
    material.userData.downloadTextureUrl = imageUrl;
  }

  return material;
}

function assignExportMaterialNames(root) {
  const used = new Map();

  root.traverse((child) => {
    if (!child.isMesh || !child.material) return;

    const materials = Array.isArray(child.material) ? child.material : [child.material];
    materials.forEach((material, index) => {
      const baseName = safeFileName(material.name || `${child.name || "mesh"}_material_${index + 1}`);
      const count = used.get(baseName) || 0;
      used.set(baseName, count + 1);
      material.name = count === 0 ? baseName : `${baseName}_${count + 1}`;
    });
  });
}

function loadTexture(url) {
  return new Promise((resolve, reject) => {
    textureLoader.load(
      url,
      (texture) => {
        texture.flipY = false;
        texture.colorSpace = THREE.SRGBColorSpace;
        resolve(texture);
      },
      undefined,
      reject
    );
  });
}

function exportGltf(root, options) {
  const exporter = new GLTFExporter();

  return new Promise((resolve, reject) => {
    exporter.parse(root, resolve, reject, {
      binary: options.binary,
      embedImages: true,
      onlyVisible: true,
    });
  });
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  URL.revokeObjectURL(url);
}

async function downloadServerBakedObj(payload, fallbackFilename) {
  if (!payload.obj_download_url) {
    throw new Error("Endpoint download OBJ belum tersedia untuk customization ini.");
  }

  const response = await fetch(payload.obj_download_url, {
    headers: {
      "Accept": "application/zip, application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  });

  if (!response.ok) {
    throw new Error(await readDownloadError(response));
  }

  const blob = await response.blob();
  const filename = filenameFromContentDisposition(response.headers.get("Content-Disposition")) || fallbackFilename;
  downloadBlob(blob, filename);
}

async function readDownloadError(response) {
  const contentType = response.headers.get("Content-Type") || "";

  if (contentType.includes("application/json")) {
    const data = await response.json().catch(() => ({}));
    return data.message || "Server gagal membuat baked OBJ.";
  }

  const text = await response.text().catch(() => "");
  if (text.trim().toLowerCase().startsWith("<!doctype html")) {
    return "Server gagal membuat baked OBJ. Cek log Laravel atau log Blender untuk detail error.";
  }

  return text || "Server gagal membuat baked OBJ.";
}

function filenameFromContentDisposition(header) {
  if (!header) return null;

  const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
  if (utfMatch) return decodeURIComponent(utfMatch[1].replace(/"/g, ""));

  const match = header.match(/filename="?([^"]+)"?/i);
  return match ? match[1] : null;
}

function safeFileName(value) {
  return String(value || "customized-model")
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9._-]+/g, "-")
    .replace(/^-+|-+$/g, "")
    || "customized-model";
}

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Download Failed",
    text: message,
    confirmButtonColor: "#f7ba2c",
  });
}
