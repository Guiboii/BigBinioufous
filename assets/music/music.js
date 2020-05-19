import "../../node_modules/jquery/dist/jquery.js";
import "../../node_modules/bootstrap/dist/js/bootstrap.min.js";
import '../music/music.css';

import * as THREE from '../libs/three.module.js';
import { GLTFLoader } from '../libs/GLTFLoader.js';

import '../music/Player.js';

var canvas,
    clock,
    mixer,
    actions,
    activeAction,
    previousAction,
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
    clock = new THREE.Clock();
    camera = new THREE.PerspectiveCamera(18, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.set(0, 4, 1.97);
    camera.rotateY(Math.PI / 2);


    // lights

    var light = new THREE.HemisphereLight(white, yellow, 0.7);
    scene.add(light);

    light = new THREE.DirectionalLight(white, 0.1);
    light.position.set(-6, 2, 0);
    //scene.add(light);

    light = new THREE.DirectionalLight(white, 0.3);
    light.position.set(6, 2, 0);
    scene.add(light);

    roomGeo(20, 10, 2);

    // model

    var loader = new GLTFLoader();
    loader.load('../build/images/Binioufou_Final4.gltf', function (gltf) {

        model = gltf.scene;
        let fileAnimations = gltf.animations;
        scene.add(model);

        model.scale.set(0.15, 0.15, 0.15);
        model.position.set(-8.5, 4.61, 1.7);
        model.rotateY(Math.PI / 2);
        mixer = new THREE.AnimationMixer(model);
        let idleAnim = THREE.AnimationClip.findByName(fileAnimations, 'samba_2');
        let nextAnim = THREE.AnimationClip.findByName(fileAnimations, 'playing2');
        idle = mixer.clipAction(idleAnim);
        next = mixer.clipAction(nextAnim);
        idle.play();

    }, undefined, function (e) {
        //console.error(e);
    });

    // sound system 
    var loader = new GLTFLoader();
    loader.load('../build/images/SoundSystem.gltf', function (gltf) {

        model = gltf.scene;
        model.name = "music";
        scene.add(model);
        model.scale.set(0.7, 0.7, 0.7);
        model.position.set(-9, 0, 2);
        model.rotateY(Math.PI / 2);
        loaderAnim.className = "isloaded";
    }, undefined, function (e) {
        //console.error(e);
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
    planeLeft.name = "planeLeft";
    planeLeft.position.y = - planeLeft.position.x / 2;
    planeLeft.rotateY(Math.PI / 2);
    scene.add(planeLeft);

}


function fadeToAction(name, duration) {

    previousAction = activeAction;
    activeAction = actions[name];

    if (previousAction !== activeAction) {

        previousAction.fadeOut(duration);

    }

    activeAction.reset().setEffectiveTimeScale(1).setEffectiveWeight(1).fadeIn(duration).play();

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
    if (intersects[0]) {
        var object = intersects[0].object.parent;
        console.log(object.name);
        if (object.name === 'SoundSystem') {
            if (!currentlyAnimating) {
                currentlyAnimating = true;
                playModifierAnimation(idle, 0.1, next, 0.1);
            }
        }
        else if (object.name === 'schedule') {
            location.href = "/schedule";
        }
    }
}


function animate() {


    render();
    requestAnimationFrame(animate);



}

function render() {

    var delta = clock.getDelta();
    if (mixer) {
        mixer.update(delta);
    }

    renderer.render(scene, camera);

}
