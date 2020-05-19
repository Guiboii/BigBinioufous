import "../../node_modules/jquery/dist/jquery.js";
import "../../node_modules/bootstrap/dist/js/bootstrap.min.js";

import * as THREE from '../libs/three.module.js';
import { OrbitControls } from '../libs/OrbitControls.js';
import { GLTFLoader } from '../libs/GLTFLoader.js';

var canvas,
    clock,
    mixer,
    currentlyAnimating,
    next,
    carpet,
    camera,
    scene,
    renderer,
    model,
    idle,
    discoBall,
    mouse = new THREE.Vector2(),
    raycaster = new THREE.Raycaster(),
    loaderAnim = document.querySelector('.loading');


var red_wall = 0xBC2727;
var yellow = 0xF2B233;
var green = 0x1F6652;
var white = 0xffffff;
var black = 0x000000;

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
    camera.position.set(0, 2, 12);

    // orbit controls
    var controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 2, 0);
    controls.enableKeys = false;
    controls.enablePan = false;
    controls.enableZoom = false;
    controls.update();

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

    // carpet
    var carpetGeo = new THREE.CircleGeometry(5, 64);
    var carpet = new THREE.Mesh(
        carpetGeo,
        new THREE.MeshPhongMaterial({ color: yellow }));
    carpet.position.y = 0.01;
    carpet.rotateX(- Math.PI / 2);
    carpet.name = "carpet";
    scene.add(carpet);

    // discoball
    var discoMaterial = new THREE.MeshPhysicalMaterial({
        color: white,
        emissive: 0x939393,
        metalness: 1,
        roughness: 0.5,
        flatShading: true,
        reflectivity: 1.0,
        premultipliedAlpha: true
    });
    var discoGeometry = new THREE.SphereBufferGeometry(1.2, 24, 24);
    discoBall = new THREE.Mesh(discoGeometry, discoMaterial);
    discoBall.position.y = 7;
    discoBall.name = "disco";
    scene.add(discoBall);

    // flyer back
    var video = document.getElementById('video1');
    video.play();
    var texture = new THREE.VideoTexture(video);
    texture.minFilter = THREE.LinearFilter;
    texture.magFilter = THREE.LinearFilter;
    texture.format = THREE.RGBFormat;
    texture.encoding = THREE.sRGBEncoding;

    var flyerBack = new THREE.Mesh(
        new THREE.PlaneBufferGeometry(4.5, 6),
        new THREE.MeshToonMaterial({
            color: 0xffffff,
            map: texture,

        }));
    flyerBack.position.set(-5.5, 5, -9.9);
    flyerBack.name = "joinUS";
    scene.add(flyerBack);


    // flyer right
    var video = document.getElementById('video2');
    video.play();
    var texture = new THREE.VideoTexture(video);
    texture.minFilter = THREE.LinearFilter;
    texture.magFilter = THREE.LinearFilter;
    texture.format = THREE.RGBFormat;
    texture.encoding = THREE.sRGBEncoding;

    var flyerRightVid = new THREE.Mesh(
        new THREE.PlaneBufferGeometry(5.5, 5.3),
        new THREE.MeshToonMaterial({ color: 0xffffff, map: texture }));
    flyerRightVid.position.set(9.88, 6.1, 2);
    flyerRightVid.rotateY(- Math.PI / 2);
    flyerRightVid.name = "soucoupe";
    scene.add(flyerRightVid);

    var texture = new THREE.TextureLoader().load("../build/images/flyer002.png");
    var flyerRight = new THREE.Mesh(
        new THREE.PlaneBufferGeometry(6, 8),
        new THREE.MeshToonMaterial({ color: 0xffffff, map: texture }));
    flyerRight.position.set(9.9, 5, 2);
    flyerRight.rotateY(- Math.PI / 2);
    flyerRight.name = "schedule";
    scene.add(flyerRight);


    // model

    var loader = new GLTFLoader();
    loader.load('../build/images/Binioufou_Final4.gltf', function (gltf) {

        model = gltf.scene;
        let fileAnimations = gltf.animations;
        model.scale.set(1, 1, 1);
        model.position.z = 4;
        scene.add(model);
        model.name = 'Biniou';

        mixer = new THREE.AnimationMixer(model);
        let idleAnim = THREE.AnimationClip.findByName(fileAnimations, 'idle');
        let nextAnim = THREE.AnimationClip.findByName(fileAnimations, 'waving2');
        let carpetAnim = THREE.AnimationClip.findByName(fileAnimations, 'foryou2');
        idle = mixer.clipAction(idleAnim);
        next = mixer.clipAction(nextAnim);
        carpet = mixer.clipAction(carpetAnim);
        idle.play();

    }, undefined, function (e) {
        console.error(e);
    });




    // desk 
    var loader = new GLTFLoader();
    loader.load('../build/images/Desk.gltf', function (gltf) {

        model = gltf.scene;
        model.scale.set(0.5, 0.5, 0.5);
        model.position.set(7, 0, -4.5);
        model.rotateY(Math.PI);
        scene.add(model);
        model.name = 'desk';

    }, undefined, function (e) {
        console.error(e);
    });


    // sound system 
    var loader = new GLTFLoader();
    loader.load('../build/images/SoundSystem.gltf', function (gltf) {

        model = gltf.scene;
        model.scale.set(0.7, 0.7, 0.7);
        model.position.set(-9, 0, 2);
        model.rotateY(Math.PI / 2);
        scene.add(model);
        model.name = 'music';

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
    planeBack.name = 'planeBack';
    scene.add(planeBack);

    var planeLeft = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
    planeLeft.position.x = - height;
    planeLeft.position.y = - planeLeft.position.x / 2;
    planeLeft.rotateY(Math.PI / 2);
    scene.add(planeLeft);

}

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

function raycast(e, touch = false) {
    console.log(scene.children);
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

        if (object.name === 'planeBack') {
            if (!currentlyAnimating) {
                currentlyAnimating = true;
                playModifierAnimation(idle, 0.25, next, 0.25);
            }
        }
        else if (object.parent.name === 'SoundSystem') {
            location.href = "/music";
        }
        else if (object.parent.name === 'Desk') {
            location.href = "/story";
        }
        else if (object.name === 'soucoupe') {
            location.href = "/schedule";
        }
        else if (object.name === 'joinUS') {
            location.href = "/join";
        }
    }
}

function onWindowResize() {

    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();

    renderer.setSize(window.innerWidth, window.innerHeight);

}

function animate() {


    requestAnimationFrame(animate);
    render();


}

function render() {

    var delta = clock.getDelta();
    // controls.update();
    if (mixer) {
        mixer.update(delta);
    }
    discoBall.rotation.y += 0.005;

    renderer.render(scene, camera);

}