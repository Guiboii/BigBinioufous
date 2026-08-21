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

// World-space anchors of the SoundSystem furniture's front panel (a near-flat plane at x≈-8.406), measured once via a camera->model raycast on a reference render. Reprojected on every resize via camera.project() so the small audio screen (waveform + buttons + playlist) stays glued to the furniture while the 3D scene keeps its own full-screen layout.
var PANEL_PLANE_X = -8.406;
var PANEL_Z_LEFT = 3.298;
var PANEL_Z_RIGHT = 0.734;
var SCREEN_Y_TOP = 4.5625;
var SCREEN_Y_BOTTOM = 4.19978;
var BUTTONS_Y_TOP = 4.19978;
var BUTTONS_Y_BOTTOM = 3.82679;
var PLAYLIST_Y_TOP = 3.82679;
var PLAYLIST_Y_BOTTOM = 2.871574;

// On small screens the 3D furniture is unreadable, so it's never initialized; Player.js keeps running independently either way.
//
// This must stay AFTER the "var PANEL_*"/"var SCREEN_*" declarations above: init() synchronously calls updateOverlayPosition(), which reads those constants immediately. "var" hoists the declaration but not the assignment, so calling init() too early leaves them all `undefined`, producing wildly wrong positions.
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

// yTop/yBottom are world-space coordinates on the front panel's plane, not pixels. Returns the matching screen rectangle for the current window.
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
  // camera.matrixWorldInverse is only recalculated by a render (or explicitly here): without this, the first call, before animate()'s first frame, projects with a stale camera matrix and produces wildly wrong positions.
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

// Popup handling (login + setlist management): the same trigger/dialog pattern is reused twice on this page, factored out here rather than duplicated. Each dialog closes via any [data-close] element inside it.
function getFocusable(container) {
  return Array.prototype.slice
    .call(container.querySelectorAll('input, textarea, select, button, a[href]'))
    .filter(function (el) {
      return !el.disabled && el.offsetParent !== null;
    });
}

// triggerIds is either one id, or an array of ids for several buttons opening the same modal. Focus on close always returns to the first trigger found on the page rather than the one actually clicked (not tracked individually): a reasonable fallback since both are functionally equivalent.
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

// Large video overlay over the 3D scene, distinct from the small audio screen still glued to the furniture via updateOverlayPosition(). Unlike initDialog() above, it has no fixed trigger button: it opens on a click on ANY YouTube badge in the playlist, listening to the same music:show-video CustomEvent already emitted for that.
(function () {
  var overlay = document.getElementById('videoOverlay');
  if (!overlay) {
    return;
  }

  // Focus goes to the close button on open. There's no single trigger to return focus to on close, since any YouTube badge in the playlist can open this overlay, so the browser falls back to its own default focus handling instead of an arbitrary forced return.
  function open() {
    overlay.classList.remove('d-none');
    var closeBtn = overlay.querySelector('.video-overlay-close');
    if (closeBtn) {
      closeBtn.focus();
    }
  }

  function close() {
    overlay.classList.add('d-none');
    // youtube-embed.js has no reason to know about the overlay mechanism (backdrop/button/Escape): a single event tells it to stop the video.
    document.dispatchEvent(new CustomEvent('music:close-video'));
  }

  document.addEventListener('music:show-video', open);

  Array.prototype.slice.call(overlay.querySelectorAll('[data-close]')).forEach(function (el) {
    el.addEventListener('click', close);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.classList.contains('d-none')) {
      close();
    }
  });
})();
