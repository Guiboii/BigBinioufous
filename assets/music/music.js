import 'jquery';
import 'bootstrap';
import './music.css';

import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

import './Player.js';
import './setlist-manage.js';
import './youtube-embed.js';

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

var red_wall = 0xbc2727;
var yellow = 0xf2b233;
var green = 0x1f6652;
var white = 0xffffff;
var black = 0x000000;

// Ancrages de la face avant du meuble SoundSystem (plan quasi plat, x≈-8.406
// dans le repère monde), mesurés une fois par raycast caméra->modèle sur un
// rendu de référence, cf. section "Cadrage d'un overlay 2D..." de CLAUDE.md :
// contrairement au vw/vh (qui suppose une mise à l'échelle linéaire avec la
// fenêtre), une caméra en perspective ne projette pas ainsi dès que le ratio
// largeur/hauteur change, d'où le décalage observé sur les fenêtres larges.
// Reprojeter ces points 3D fixes via camera.project() à chaque resize donne
// une position d'overlay exacte à toute taille/ratio de fenêtre.
var PANEL_PLANE_X = -8.406;
var PANEL_Z_LEFT = 3.298;
var PANEL_Z_RIGHT = 0.734;
var SCREEN_Y_TOP = 4.5625;
var SCREEN_Y_BOTTOM = 4.19978;
var BUTTONS_Y_TOP = 4.19978;
var BUTTONS_Y_BOTTOM = 3.82679;
var PLAYLIST_Y_TOP = 3.82679;
var PLAYLIST_Y_BOTTOM = 2.871574;

// .is-mobile posée par templates/music/index.html.twig avant le 1er rendu
// (cf. commentaire là-bas) : sur petit écran, le meuble 3D est illisible,
// donc on ne l'initialise même pas. Player.js (import plus haut) tourne
// indépendamment de tout ça et reste actif dans les deux cas.
if (!document.documentElement.classList.contains('is-mobile')) {
  init();
  animate();
}

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
  loader.load(
    window.BB_ASSETS['mascotte/Binioufou_Final4.gltf'],
    function (gltf) {
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
    },
    undefined,
    function (e) {
      //console.error(e);
    },
  );

  // sound system
  var loader = new GLTFLoader();
  loader.load(
    window.BB_ASSETS['music/SoundSystem.gltf'],
    function (gltf) {
      model = gltf.scene;
      model.name = 'music';
      scene.add(model);
      model.scale.set(0.7, 0.7, 0.7);
      model.position.set(-9, 0, 2);
      model.rotateY(Math.PI / 2);
      loaderAnim.className = 'isloaded';
    },
    undefined,
    function (e) {
      //console.error(e);
    },
  );

  window.addEventListener('click', (e) => raycast(e));
  window.addEventListener('touchend', (e) => raycast(e, true));
  window.addEventListener('resize', onWindowResize, false);

  updateOverlayPosition();
}

function projectPoint(x, y, z) {
  var v = new THREE.Vector3(x, y, z);
  v.project(camera);
  return {
    x: ((v.x + 1) / 2) * window.innerWidth,
    y: ((1 - v.y) / 2) * window.innerHeight,
  };
}

// yTop/yBottom : coordonnées monde (repère du plan de la face avant), pas
// des pixels. Renvoie le rectangle écran correspondant pour la fenêtre
// actuelle.
function projectPanelBox(yTop, yBottom) {
  var topLeft = projectPoint(PANEL_PLANE_X, yTop, PANEL_Z_LEFT);
  var bottomRight = projectPoint(PANEL_PLANE_X, yBottom, PANEL_Z_RIGHT);
  return {
    left: topLeft.x,
    top: topLeft.y,
    width: bottomRight.x - topLeft.x,
    height: bottomRight.y - topLeft.y,
  };
}

function applyBox(el, box) {
  if (!el) return;
  el.style.position = 'fixed';
  el.style.left = box.left + 'px';
  el.style.top = box.top + 'px';
  el.style.width = box.width + 'px';
  el.style.height = box.height + 'px';
}

function updateOverlayPosition() {
  // camera.matrixWorldInverse n'est recalculée que par le rendu (ou ici,
  // explicitement) : sans ça, le premier appel (avant la 1re frame rendue
  // par animate()) projette avec une matrice caméra encore périmée et
  // produit des positions aberrantes.
  camera.updateMatrixWorld();

  applyBox(
    document.querySelector('.player-wrap .row:first-child'),
    projectPanelBox(SCREEN_Y_TOP, SCREEN_Y_BOTTOM),
  );
  applyBox(
    document.querySelector('.player-wrap .row.playerButtons'),
    projectPanelBox(BUTTONS_Y_TOP, BUTTONS_Y_BOTTOM),
  );
  applyBox(
    document.querySelector('.playlist-wrap'),
    projectPanelBox(PLAYLIST_Y_TOP, PLAYLIST_Y_BOTTOM),
  );
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
  planeLeft.name = 'planeLeft';
  planeLeft.position.y = -planeLeft.position.x / 2;
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
  setTimeout(
    function () {
      from.enabled = true;
      to.crossFadeTo(from, tSpeed, true);
      currentlyAnimating = false;
    },
    to._clip.duration * 1000 - (tSpeed + fSpeed) * 1000,
  );
}

function onWindowResize() {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();

  renderer.setSize(window.innerWidth, window.innerHeight);
  updateOverlayPosition();
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
    } else if (object.name === 'schedule') {
      location.href = '/schedule';
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

// Gestion des popups (connexion + gestion de la setlist, cf.
// templates/music/index.html.twig) : même pattern trigger/dialog réutilisé 2
// fois sur cette page, factorisé plutôt que dupliqué. Chaque dialog se ferme
// via n'importe quel élément [data-close] à l'intérieur (bouton "fermer" du
// composant form-dialog, ou le fond semi-transparent pour la modale de
// setlist qui n'a pas d'équivalent form-dialog réutilisable, cf. plan).
function getFocusable(container) {
  return Array.prototype.slice
    .call(container.querySelectorAll('input, textarea, select, button, a[href]'))
    .filter(function (el) {
      return !el.disabled && el.offsetParent !== null;
    });
}

// triggerIds : un id, ou un tableau d'ids pour plusieurs boutons ouvrant la
// même modale (cf. #uploadNew + #setlistManageTrigger, tous deux liés à
// #setlistManageDialog, retour utilisatrice le 2026-08-13). Le focus à la
// fermeture revient au 1er déclencheur trouvé sur la page plutôt qu'à celui
// réellement cliqué (pas suivi individuellement) : repli raisonnable, les
// deux sont de toute façon équivalents fonctionnellement.
function initDialog(triggerIds, dialogId) {
  var ids = Array.isArray(triggerIds) ? triggerIds : [triggerIds];
  var triggers = ids
    .map(function (id) {
      return document.getElementById(id);
    })
    .filter(Boolean);
  var dialog = document.getElementById(dialogId);

  if (!triggers.length || !dialog) {
    return;
  }

  function open() {
    dialog.classList.remove('d-none');
    var focusable = getFocusable(dialog);
    if (focusable.length) {
      focusable[0].focus();
    }
  }

  function close() {
    dialog.classList.add('d-none');
    triggers[0].focus();
  }

  triggers.forEach(function (trigger) {
    trigger.addEventListener('click', open);
  });

  Array.prototype.slice.call(dialog.querySelectorAll('[data-close]')).forEach(function (el) {
    el.addEventListener('click', close);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !dialog.classList.contains('d-none')) {
      close();
    }
  });
}

initDialog('musicLoginTrigger', 'musicLoginForm');
initDialog(['setlistManageTrigger', 'uploadNew'], 'setlistManageDialog');
