import * as THREE from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

/* GET CSRF TOKEN ONCE */
const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

async function processModel(modelId, modelUrl) {
    const loader = new GLTFLoader();
    console.log("Processing model:", modelId);

    loader.load(modelUrl, async (gltf) => {

        /*
        GENERATE PARTS
        */

        const meshes = [];

        gltf.scene.traverse((child) => {
            if (child.isMesh) {

                const name = child.name && child.name.trim() !== ""
                    ? child.name
                    : `Mesh_${meshes.length+1}`;

                meshes.push(name);
            }
        });

        await fetch(`/models/${modelId}/generate-parts`, {
            method:'POST',
            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body:JSON.stringify({meshes})
        });

        /* GENERATE THUMBNAIL */

        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0xdbdbdb);

        const camera = new THREE.PerspectiveCamera(45,5/3,0.1,1000);
        const renderer = new THREE.WebGLRenderer({antialias:true});

        renderer.setSize(800, 480);

        const container = document.getElementById('thumbnailGenerator');
        if(container){
            container.appendChild(renderer.domElement);
        }

        const light = new THREE.AmbientLight(0xffffff,1);
        scene.add(light);

        scene.add(gltf.scene);

        const box = new THREE.Box3().setFromObject(gltf.scene);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        const maxDim = Math.max(size.x,size.y,size.z);
        const fov = camera.fov*(Math.PI/180);

        let cameraZ = Math.abs(maxDim/2/Math.tan(fov/2));
        cameraZ *= 1.5;

        camera.position.set(center.x,center.y,center.z+cameraZ);
        camera.lookAt(center);

        renderer.render(scene,camera);

        const base64 = renderer.domElement.toDataURL("image/png");

        await fetch(`/models/${modelId}/save-thumbnail`,{
            method:'POST',
            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body:JSON.stringify({thumbnail:base64})
        });

        console.log("Marking ready:", modelId);
        await fetch(`/models/${modelId}/mark-ready`,{
            method:'POST',
            headers:{
                "X-CSRF-TOKEN": csrfToken
            }
        });

        // Update list model Admin
        const thumbnailContainer = document.getElementById(`thumbnail-container-${modelId}`);
        if (thumbnailContainer) {
            thumbnailContainer.innerHTML = `<img src="${base64}" width="60" class="rounded">`;
        }
        const containerReady = document.getElementById(`status-container-${modelId}`);
        if (containerReady) {
            containerReady.innerHTML = '<span class="badge bg-success">Ready</span>';
        }

        // UPDATE USER CARD
        const card = document.querySelector(`[data-model="${modelId}"]`);
        if (card) {
            // update thumbnail
            const img = card.querySelector("img");
            if (img) {
                img.src = base64;
            }
            // update badge processing
            const badge = card.querySelector(".processing-badge");
            if (badge) {
                badge.remove();
            }
            // tampilkan tombol publish
            const publishContainer = card.querySelector(".publish-container");
            if (publishContainer) {
                publishContainer.style.display = "block";
            }
        }

        document.querySelector(`#model-row-${modelId}`)
            ?.classList.remove("processing");

    }, undefined, (error) => {
        console.error("Failed to load GLB:", modelUrl, error);
    });
}

window.processModel = processModel;
