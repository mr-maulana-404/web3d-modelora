/* Sidebar */
const sidebar = document.getElementById("sidebar");

window.toggleSidebar = function () {
  if (sidebar) sidebar.classList.toggle("show");
};

/* Three.js Viewer (Modal) */
import * as THREE from "three";
import { OrbitControls } from "three/examples/jsm/controls/OrbitControls.js";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

let scene, camera, renderer, controls;
let currentModel = null;
let animationId = null;

function initViewer(container) {
  // Scene
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0xdddddd);

  // Camera
  camera = new THREE.PerspectiveCamera(
    45,
    container.clientWidth / container.clientHeight,
    0.1,
    1000
  );
  camera.position.set(0, 1.2, 3);

  // Renderer
  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(container.clientWidth, container.clientHeight);
  renderer.setPixelRatio(window.devicePixelRatio);

  // Clear container sebelum append
  container.innerHTML = "";
  container.appendChild(renderer.domElement);

  // Controls
  controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.05;
  controls.autoRotate = true;
  controls.autoRotateSpeed = 5.0;
  controls.enablePan = true;

  // Lighting
  const ambient = new THREE.AmbientLight(0xffffff, 0.8);
  scene.add(ambient);

  const dirLight = new THREE.DirectionalLight(0xffffff, 1);
  dirLight.position.set(3, 5, 2);
  scene.add(dirLight);

  // Ground (optional, biar lebih bagus)
/*const ground = new THREE.Mesh(
  new THREE.PlaneGeometry(20, 20),
  new THREE.MeshStandardMaterial({ color: 0xf2f2f2 })
  );
  ground.rotation.x = -Math.PI / 2;
  ground.position.y = -0.001;
  scene.add(ground);*/

  animate();
}

function animate() {
  animationId = requestAnimationFrame(animate);

  if (controls) controls.update();
  if (renderer && scene && camera) renderer.render(scene, camera);
}

function stopViewer() {
  // stop animation loop
  if (animationId) cancelAnimationFrame(animationId);

  // dispose model
  if (currentModel) {
    scene.remove(currentModel);

    currentModel.traverse((child) => {
      if (child.isMesh) {
        child.geometry?.dispose();

        if (child.material) {
          if (Array.isArray(child.material)) {
            child.material.forEach((m) => m.dispose());
          } else {
            child.material.dispose();
          }
        }

        // dispose texture map jika ada
        if (child.material?.map) child.material.map.dispose();
      }
    });

    currentModel = null;
  }

  // dispose renderer
  if (renderer) {
    renderer.dispose();
    renderer.forceContextLoss();
    renderer.domElement = null;
    renderer = null;
  }

  scene = null;
  camera = null;
  controls = null;
}

function loadModel(gltfUrl) {
  return new Promise((resolve, reject) => {
    const loader = new GLTFLoader();

    loader.load(
      gltfUrl,
      (gltf) => {
        resolve(gltf);
      },
      undefined,
      (err) => {
        reject(err);
      }
    );
  });
}

function fitCameraToObject(object, offset = 1.4) {
  const box = new THREE.Box3().setFromObject(object);
  const size = box.getSize(new THREE.Vector3());
  const center = box.getCenter(new THREE.Vector3());

  const maxDim = Math.max(size.x, size.y, size.z);
  const fov = camera.fov * (Math.PI / 180);
  let cameraZ = Math.abs((maxDim / 2) / Math.tan(fov / 2));

  cameraZ *= offset;

  camera.position.set(center.x, center.y + maxDim * 0.2, center.z + cameraZ);
  camera.lookAt(center);

  controls.target.copy(center);
  controls.update();
}

function resizeRendererToDisplaySize(container) {
  if (!renderer || !camera) return;

  const width = container.clientWidth;
  const height = container.clientHeight;

  renderer.setSize(width, height);
  camera.aspect = width / height;
  camera.updateProjectionMatrix();
}

/* Modal Handler */
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("modelDetailModal");
  const viewerContainer = document.getElementById("viewerContainer");

  if (!modal || !viewerContainer) return;

  modal.addEventListener("shown.bs.modal", async function (event) {
    const card = event.relatedTarget;
    const data = JSON.parse(card.getAttribute("data-model-json"));

    // set text
    document.getElementById("modalModelName").textContent = data.name;
    document.getElementById("modalModelCategory").textContent =
      (data.age_category ?? "Unknown") + " | " + (data.gender_category ?? "Unknown");
    document.getElementById("modalModelDescription").textContent =
      data.description ?? "There is still no explanatory description for this model";

    // customize link
    const btnCustomize = document.getElementById("btnCustomize");
    if (btnCustomize) {
      btnCustomize.href = `/gallery/${data.slug}/customize`;
    }

    // OWNER ACTIONS
    const ownerActions = document.getElementById("ownerActions");

    if (ownerActions && data.user_id === window.currentUserId) {

        ownerActions.style.display = "block";

        // delete
        const formDelete = document.getElementById("formDelete");
        formDelete.action = `/gallery/${data.id}`;

        formDelete.onsubmit = function(e) {
            e.preventDefault(); 

            Swal.fire({
                title: 'Delete Model Data?',
                text: "Deleted data cannot be recovered!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Delete!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    formDelete.submit();
                }
            });
        };

        // edit
        document.getElementById("btnEditModel").href =
            `/gallery/${data.id}/edit`;

        // publish
        const formPublish = document.getElementById("formPublish");
        formPublish.action = `/gallery/${data.id}/publish`;

        const btnPublish = document.getElementById("btnPublish");

        if (data.is_published) {
            btnPublish.textContent = "Unpublish";
        } else {
            btnPublish.textContent = "Publish";
        }
    }

    // init viewer
    initViewer(viewerContainer);

    // gltf URL dari storage
    const gltfUrl = `/storage/${data.model_path}`;

    // loading placeholder
    viewerContainer.style.position = "relative";
    viewerContainer.insertAdjacentHTML(
      "beforeend",
      `<div id="viewerLoading"
        style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#333;">
        Loading 3D...
      </div>`
    );

    try {
      const gltf = await loadModel(gltfUrl);

      currentModel = gltf.scene;
      scene.add(currentModel);

      // fit camera
      fitCameraToObject(currentModel);

      // hapus loading
      const loading = document.getElementById("viewerLoading");
      if (loading) loading.remove();

      // resize setelah model masuk
      resizeRendererToDisplaySize(viewerContainer);
    } catch (err) {
      console.error(err);

      const loading = document.getElementById("viewerLoading");
      if (loading) {
        loading.textContent = "Gagal load model.";
      }
    }
  });

  modal.addEventListener("hidden.bs.modal", function () {
    stopViewer();
    viewerContainer.innerHTML = "";
  });

  // kalau user resize window
  window.addEventListener("resize", () => {
    resizeRendererToDisplaySize(viewerContainer);
  });
});
