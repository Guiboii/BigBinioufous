import 'jquery';
import 'bootstrap';
// import './story.css';

import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

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

var red_wall = 0xbc2727;
var yellow = 0xf2b233;
var green = 0x1f6652;
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
  camera.rotateY(-Math.PI);

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
  loader.load(
    window.BB_ASSETS['mascotte/Binioufou_Final4.gltf'],
    function (gltf) {
      model = gltf.scene;
      let fileAnimations = gltf.animations;
      model.scale.set(0.12, 0.12, 0.12);
      model.position.set(6.6, 2.25, -6.5);
      model.rotateY(-Math.PI - 50);
      scene.add(model);

      mixer = new THREE.AnimationMixer(model);
      let idleAnim = THREE.AnimationClip.findByName(fileAnimations, 'twist');
      let nextAnim = THREE.AnimationClip.findByName(fileAnimations, 'taada2');
      idle = mixer.clipAction(idleAnim);
      next = mixer.clipAction(nextAnim);
      idle.play();
    },
    undefined,
    function (e) {
      console.error(e);
    },
  );

  // Soucoupe 3D MODEL
  var loader = new GLTFLoader();
  loader.load(
    window.BB_ASSETS['story/Soucoupe.gltf'],
    function (gltf) {
      model = gltf.scene;
      model.position.set(7, 3, -6);
      model.scale.set(0.05, 0.05, 0.05);
      model.rotateY(Math.PI);
      model.name = 'soucoupe';
      scene.add(model);

      mixerC = new THREE.AnimationMixer(model);
      let flyAnim = THREE.AnimationClip.findByName(gltf.animations, 'flying');
      let fly = mixerC.clipAction(flyAnim);
      fly.play();
    },
    undefined,
    function (e) {
      console.error(e);
    },
  );
  // desk
  var loader = new GLTFLoader();
  loader.load(
    window.BB_ASSETS['story/Desk.gltf'],
    function (gltf) {
      model = gltf.scene;
      model.scale.set(0.5, 0.5, 0.5);
      model.position.set(6.5, 0, -5.5);
      model.rotateY(Math.PI);
      model.name = 'desk';
      scene.add(model);
      loaderAnim.className = 'isloaded';
    },
    undefined,
    function (e) {
      console.error(e);
    },
  );

  // trieurs (pile de classeurs posée sur le bureau) : cf. le même bloc dans
  // assets/mascotte/mascotte.js, coordonnées décalées de (-0.5, 0, -1) ici
  // (Desk.gltf positionné différemment sur cette page).
  addTrieur('trieurAccounting', 2.2, 2.58);
  addTrieur('trieurMusic', 2.58, 2.96);
  addTrieur('trieurAdmin', 2.96, 3.34);
  addTrieur('trieurOther', 3.34, 3.7);

  window.addEventListener('click', (e) => raycast(e));
  window.addEventListener('touchend', (e) => raycast(e, true));
  window.addEventListener('resize', onWindowResize, false);
}

function addTrieur(name, yMin, yMax) {
  // profondeur (Z) volontairement réduite par rapport au modèle réel
  // (contrairement à mascotte.js) : caméra ici très proche et quasi dans
  // l'axe de la pile, une boîte aussi profonde que la pile réelle fait se
  // chevaucher les zones de clic de deux trieurs adjacents sur un même rayon
  var trieur = new THREE.Mesh(
    new THREE.BoxBufferGeometry(1.28, yMax - yMin, 0.4),
    new THREE.MeshPhongMaterial({ color: 0xffffff, transparent: true, opacity: 0 }),
  );
  trieur.position.set(7.56, (yMin + yMax) / 2, -5.775);
  trieur.name = name;
  scene.add(trieur);
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
  planeBottom.rotateX(-Math.PI / 2);
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
  planeRight.rotateY(-Math.PI / 2);
  scene.add(planeRight);

  var planeBack = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
  planeBack.position.z = -height;
  planeBack.position.y = -planeBack.position.z / 2;
  scene.add(planeBack);

  var planeLeft = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
  planeLeft.position.x = -height;
  planeLeft.position.y = -planeLeft.position.x / 2;
  planeLeft.rotateY(Math.PI / 2);
  scene.add(planeLeft);
}

function onWindowResize() {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();

  renderer.setSize(window.innerWidth, window.innerHeight);
}

// GLTFLoader renomme les nœuds en collision avec le nom de la scène GLTF
// d'origine (Desk.gltf a une scène ET un nœud tous deux nommés "Desk" :
// le nœud devient "Desk_1" au chargement), donc object.parent.name ne
// vaut jamais "Desk" pour les meshes du bureau. On remonte l'arborescence
// jusqu'au groupe qu'on a nommé nous-mêmes (model.name = 'desk') plutôt
// que de dépendre de ce renommage interne.
function isDescendantOf(object, name) {
  var current = object;
  while (current) {
    if (current.name === name) {
      return true;
    }
    current = current.parent;
  }
  return false;
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

  // Avant : clic sur un "trieur" (classeur du bureau) -> /desk/files/{espace},
  // clic sur la soucoupe -> /join. Espace membre coupé sur cette branche
  // prod_prov (cf. MemberAreaDisabledSubscriber), retirés pour ne pas
  // surprendre avec une redirection vers l'accueil.
  if (intersects[0]) {
    var object = intersects[0].object;
    console.log(object.name);
    if (isDescendantOf(object, 'desk')) {
      if (!currentlyAnimating) {
        currentlyAnimating = true;
        playModifierAnimation(idle, 0.25, next, 0.25);
      }
    }
  }
}

function playModifierAnimation(from, fSpeed, to, tSpeed) {
  to.setLoop(THREE.LoopOnce);
  to.reset();
  to.play();
  from.crossFadeTo(to, fSpeed, true);
  setTimeout(
    function () {
      from.enabled = true;
      to.crossFadeTo(from, tSpeed, true);
      currentlyAnimating = false;
    },
    to._clip.duration * 1000 - (tSpeed + fSpeed) * 1000,
  );
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
