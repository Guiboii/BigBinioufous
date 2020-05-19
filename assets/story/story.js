import "../../node_modules/jquery/dist/jquery.js";
import "../../node_modules/bootstrap/dist/js/bootstrap.min.js";
// import '../story/story.css';

import * as THREE from '../libs/three.module.js';
import { GLTFLoader } from '../libs/GLTFLoader.js';

var canvas,
    clock,
    mixer,
    mixerC,
    currentlyAnimating,
    next,
    camera,
    scene,
    renderer,
    model,
    idle,
    raycaster = new THREE.Raycaster(),
    mouse = new THREE.Vector2(),
    loaderAnim = document.querySelector('.loading');



var red_wall = 0xBC2727;
var yellow = 0xF2B233;
var green = 0x1F6652;
var white = 0xffffff;

init();
animate();

function init() {

    canvas = document.querySelector('#c');

    renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.outputEncoding = THREE.sRGBEncoding;
    document.body.appendChild(renderer.domElement);

    scene = new THREE.Scene();
    scene.background = new THREE.Color(0xe0e0e0);
    scene.fog = new THREE.Fog(0xe0e0e0, 20, 100);

    clock = new THREE.Clock();
    camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.set(5.6, 2.8, -7.8);
    camera.rotateY(- Math.PI);


    // lights
    var light = new THREE.HemisphereLight(white, yellow, 0.7);
    scene.add(light);

    light = new THREE.DirectionalLight(white, 0.5);
    light.position.set(-6, 2, 0);
    scene.add(light);

    light = new THREE.DirectionalLight(white, 0.5);
    light.position.set(6, 2, 0);
    scene.add(light);

    // room
    roomGeo(20, 10, 2);

    // model

    var loader = new GLTFLoader();
    loader.load('../build/images/Binioufou_Final4.gltf', function (gltf) {

        model = gltf.scene;
        let fileAnimations = gltf.animations;
        model.scale.set(0.12, 0.12, 0.12);
        model.position.set(6.6, 2.25, -6.5);
        model.rotateY(- Math.PI - 50);
        scene.add(model);

        mixer = new THREE.AnimationMixer(model);
        let idleAnim = THREE.AnimationClip.findByName(fileAnimations, 'twist');
        let nextAnim = THREE.AnimationClip.findByName(fileAnimations, 'taada2');
        idle = mixer.clipAction(idleAnim);
        next = mixer.clipAction(nextAnim);
        idle.play();

    }, undefined, function (e) {
        console.error(e);
    });

    // Soucoupe 3D MODEL
    var loader = new GLTFLoader();
    loader.load('../build/images/Soucoupe.gltf', function (gltf) {

        model = gltf.scene;
        model.position.set(7, 3, -6);
        model.scale.set(0.05, 0.05, 0.05);
        model.rotateY(Math.PI);
        model.name = "soucoupe";
        scene.add(model);

        mixerC = new THREE.AnimationMixer(model);
        let flyAnim = THREE.AnimationClip.findByName(gltf.animations, 'flying');
        let fly = mixerC.clipAction(flyAnim);
        fly.play();
    }, undefined, function (e) {
        console.error(e);
    });
    var planecontact = new THREE.Mesh(
        new THREE.PlaneBufferGeometry(1.5, 0.5),
        new THREE.MeshPhongMaterial({ color: 0xffffff, transparent: true, opacity: 0 }));
    planecontact.position.set(5.3, 2.3, -6.8);
    planecontact.rotateY(Math.PI);
    planecontact.name = "contactPlane";
    scene.add(planecontact);

    // desk 
    var loader = new GLTFLoader();
    loader.load('../build/images/Desk.gltf', function (gltf) {

        model = gltf.scene;
        model.scale.set(0.5, 0.5, 0.5);
        model.position.set(6.5, 0, -5.5);
        model.rotateY(Math.PI);
        model.name = 'desk';
        scene.add(model);
        loaderAnim.className = "isloaded";
    }, undefined, function (e) {
        console.error(e);
    });

    window.addEventListener('click', e => raycast(e));
    window.addEventListener('touchend', e => raycast(e, true));
    window.addEventListener('resize', onWindowResize, false);

}

function roomGeo(width, height, scaleY) {

    // walls
    var planeGeo = new THREE.PlaneBufferGeometry(width, height);

    var planeTop = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: yellow }));
    planeTop.scale.y = scaleY;
    planeTop.position.y = height;
    planeTop.rotateX(Math.PI / 2);
    scene.add(planeTop);

    var planeBottom = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: green }));
    planeBottom.scale.y = scaleY;
    planeBottom.rotateX(- Math.PI / 2);
    planeBottom.receiveShadow = true;
    scene.add(planeBottom);

    var planeFront = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
    planeFront.position.z = height;
    planeFront.position.y = planeFront.position.z / 2;
    planeFront.rotateY(Math.PI);
    scene.add(planeFront);

    var planeRight = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
    planeRight.position.x = height;
    planeRight.position.y = planeRight.position.x / 2;
    planeRight.rotateY(- Math.PI / 2);
    scene.add(planeRight);

    var planeBack = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
    planeBack.position.z = - height;
    planeBack.position.y = - planeBack.position.z / 2;
    scene.add(planeBack);

    var planeLeft = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
    planeLeft.position.x = - height;
    planeLeft.position.y = - planeLeft.position.x / 2;
    planeLeft.rotateY(Math.PI / 2);
    scene.add(planeLeft);

}


function onWindowResize() {

    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();

    renderer.setSize(window.innerWidth, window.innerHeight);

}
//

function raycast(e, touch = false) {
    if (touch) {
        mouse.x = 2 * (e.changedTouches[0].clientX / window.innerWidth) - 1;
        mouse.y = 1 - 2 * (e.changedTouches[0].clientY / window.innerHeight);
    } else {
        mouse.x = 2 * (e.clientX / window.innerWidth) - 1;
        mouse.y = 1 - 2 * (e.clientY / window.innerHeight);
    }
    // update the picking ray with the camera and mouse position
    raycaster.setFromCamera(mouse, camera);
    // calculate objects intersecting the picking ray
    var intersects = raycaster.intersectObjects(scene.children, true);
    console.log(intersects);
    if (intersects[0]) {
        var object = intersects[0].object;
        console.log(object.name);
        if (object.parent.name === 'Desk') {
            if (!currentlyAnimating) {
                currentlyAnimating = true;
                playModifierAnimation(idle, 0.25, next, 0.25);
            }
        }
        else if (object.name === 'contactPlane') {
            document.getElementById('contactForm').classList.remove('d-none');
        }
        else if (object.parent.name === 'Soucoupe') {
            location.href = "/join";
        }
    }
}

var close = document.querySelector('.close');
close.addEventListener('click', function () {
    document.getElementById('contactForm').classList.add('d-none');
});


function playModifierAnimation(from, fSpeed, to, tSpeed) {
    to.setLoop(THREE.LoopOnce);
    to.reset();
    to.play();
    from.crossFadeTo(to, fSpeed, true);
    setTimeout(function () {
        from.enabled = true;
        to.crossFadeTo(from, tSpeed, true);
        currentlyAnimating = false;
    }, to._clip.duration * 1000 - ((tSpeed + fSpeed) * 1000));
}

function animate() {


    requestAnimationFrame(animate);
    render();


}
function render() {

    var delta = clock.getDelta();
    if (mixer) {
        mixer.update(delta);
    }
    if (mixerC) {
        mixerC.update(delta);
    }
    renderer.render(scene, camera);

}