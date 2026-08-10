import 'jquery';
import 'bootstrap';
import './join.css';

import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

var canvas,
  clock,
  mixer,
  mixerB,
  mixerC,
  currentlyAnimating,
  next,
  carpet,
  camera,
  scene,
  renderer,
  model,
  idle,
  discoBall,
  logoB,
  mouse = new THREE.Vector2(),
  raycaster = new THREE.Raycaster(),
  loaderAnim = document.querySelector('.loading');

var red_wall = 0xbc2727;
var yellow = 0xf2b233;
var green = 0x1f6652;
var white = 0xffffff;
var dark = 0x23272b;

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
  scene.background = new THREE.Color(green);
  scene.fog = new THREE.Fog(0xe0e0e0, 20, 100);

  clock = new THREE.Clock();
  camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 100);
  camera.position.set(0, 2, 12);

  // lights
  var light = new THREE.HemisphereLight(white, yellow, 0.7);
  scene.add(light);

  light = new THREE.DirectionalLight(white, 0.2);
  light.position.set(-6, 2, 0);
  scene.add(light);

  light = new THREE.DirectionalLight(white, 0.2);
  light.position.set(6, 2, 0);
  scene.add(light);

  // carpet
  var carpetGeo = new THREE.CircleGeometry(5, 64);
  var carpet = new THREE.Mesh(carpetGeo, new THREE.MeshPhongMaterial({ color: yellow }));
  carpet.position.y = 0.01;
  carpet.rotateX(-Math.PI / 2);
  carpet.name = 'carpet';
  scene.add(carpet);

  // discoball
  var discoMaterial = new THREE.MeshPhysicalMaterial({
    color: white,
    emissive: 0x939393,
    metalness: 1,
    roughness: 0.5,
    flatShading: true,
    reflectivity: 1.0,
    premultipliedAlpha: true,
  });
  var discoGeometry = new THREE.SphereBufferGeometry(1.2, 24, 24);
  discoBall = new THREE.Mesh(discoGeometry, discoMaterial);
  discoBall.position.y = 7;
  discoBall.name = 'disco';
  scene.add(discoBall);

  var planeGeo = new THREE.PlaneBufferGeometry(30, 30);
  var planeBack = new THREE.Mesh(planeGeo, new THREE.MeshPhongMaterial({ color: red_wall }));
  planeBack.position.set(15, 2, -5);
  scene.add(planeBack);

  // logo B 3D MODEL
  var loader = new GLTFLoader();
  loader.load(
    window.BB_ASSETS['mascotte/moveB.gltf'],
    function (gltf) {
      model = gltf.scene;
      model.position.set(-10, 0, 0);
      model.scale.set(0.7, 0.7, 0.7);
      model.name = 'logoB';
      scene.add(model);

      mixerB = new THREE.AnimationMixer(model);
      let moveAnim = THREE.AnimationClip.findByName(gltf.animations, 'moveB');
      let moveB = mixerB.clipAction(moveAnim);
      moveB.play();
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
      model.position.set(10, 3, 0);
      model.scale.set(0.5, 0.5, 0.5);
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

  // model

  var loader = new GLTFLoader();
  loader.load(
    window.BB_ASSETS['mascotte/Binioufou_Final4.gltf'],
    function (gltf) {
      model = gltf.scene;
      let fileAnimations = gltf.animations;
      model.scale.set(1, 1, 1);
      model.position.z = 4;
      model.position.y = 0.1;
      //model.scale.set(1.5, 1.5, 1.5);
      scene.add(model);

      mixer = new THREE.AnimationMixer(model);

      let idleAnim = THREE.AnimationClip.findByName(fileAnimations, 'idle');
      let nextAnim = THREE.AnimationClip.findByName(fileAnimations, 'foryou2');
      let carpetAnim = THREE.AnimationClip.findByName(fileAnimations, 'waving2');
      idle = mixer.clipAction(idleAnim);
      next = mixer.clipAction(nextAnim);
      carpet = mixer.clipAction(carpetAnim);

      idle.play();
      loaderAnim.className = 'isloaded';
    },
    undefined,
    function (e) {
      console.error(e);
    },
  );

  window.addEventListener('click', (e) => raycast(e));
  window.addEventListener('touchend', (e) => raycast(e, true));
  window.addEventListener('resize', onWindowResize, false);
}

function onWindowResize() {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();

  renderer.setSize(window.innerWidth, window.innerHeight);
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
    console.log(object.parent.name);
    if (object.name === 'carpet') {
      if (!currentlyAnimating) {
        currentlyAnimating = true;
        playModifierAnimation(idle, 0.25, next, 0.25);
      }
    } else if (object.parent.name === 'LogoB') {
      document.getElementById('loginForm').classList.remove('d-none');
    } else if (object.parent.name === 'Soucoupe') {
      document.getElementById('joinForm').classList.remove('d-none');
    } else if (object.name === 'disco') {
      location.href = '/';
    }
  }
}
// show the popup forms
//
// Chaque popup (#joinForm/#loginForm, role="dialog" posé dans
// join/index.html.twig) est ouverte/fermée en gardant clavier et lecteur
// d'écran cohérents avec l'état visuel :
// - le bouton déclencheur expose son état via aria-expanded
// - à l'ouverture, le focus part sur le 1er champ du formulaire (ou le
//   bouton fermer s'il n'y a pas de champ) plutôt que de rester sur place
// - Echap ferme la popup ouverte et rend le focus au bouton déclencheur
// - un simple piège de tabulation (Tab/Shift+Tab) empêche de sortir de la
//   popup pendant qu'elle est ouverte (role="dialog" + aria-modal="true"
//   sans ça ne suffit pas : rien n'empêche réellement le focus de sortir)

var joinButton = document.querySelector('button.join');
var loginButton = document.querySelector('button.login');
var joinForm = document.getElementById('joinForm');
var loginForm = document.getElementById('loginForm');

function getFocusable(container) {
  return Array.prototype.slice
    .call(container.querySelectorAll('input, textarea, select, button, a[href]'))
    .filter(function (el) {
      return !el.disabled && el.offsetParent !== null;
    });
}

function openPopup(form, trigger) {
  form.classList.remove('d-none');
  trigger.setAttribute('aria-expanded', 'true');
  form.dataset.trigger = trigger === joinButton ? 'join' : 'login';
  var focusable = getFocusable(form);
  if (focusable.length) {
    focusable[0].focus();
  }
}

function closePopup(form) {
  form.classList.add('d-none');
  var trigger = form.dataset.trigger === 'join' ? joinButton : loginButton;
  trigger.setAttribute('aria-expanded', 'false');
  trigger.focus();
}

function isOpen(form) {
  return !form.classList.contains('d-none');
}

joinButton.addEventListener('click', function () {
  openPopup(joinForm, joinButton);
});
loginButton.addEventListener('click', function () {
  openPopup(loginForm, loginButton);
});

// close the popups
var closeJ = joinForm.querySelector('.close');
var closeL = loginForm.querySelector('.close');
closeJ.addEventListener('click', function () {
  closePopup(joinForm);
});
closeL.addEventListener('click', function () {
  closePopup(loginForm);
});

document.addEventListener('keydown', function (e) {
  var openForm = isOpen(joinForm) ? joinForm : isOpen(loginForm) ? loginForm : null;
  if (!openForm) {
    return;
  }
  if (e.key === 'Escape') {
    closePopup(openForm);
    return;
  }
  if (e.key === 'Tab') {
    var focusable = getFocusable(openForm);
    if (!focusable.length) {
      return;
    }
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }
});

function animate() {
  requestAnimationFrame(animate);
  render();
}

function render() {
  var delta = clock.getDelta();
  if (mixer) {
    mixer.update(delta);
  }
  if (mixerB) {
    mixerB.update(delta);
  }
  if (mixerC) {
    mixerC.update(delta);
  }
  discoBall.rotation.y += 0.005;
  renderer.render(scene, camera);
}
