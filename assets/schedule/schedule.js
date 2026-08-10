import 'jquery';
import 'bootstrap';
import './schedule.css';

import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

var canvas,
  clock,
  mixer,
  actions,
  activeAction,
  previousAction,
  possibleAnims,
  currentlyAnimating,
  camera,
  scene,
  renderer,
  model,
  idle,
  next,
  raycaster = new THREE.Raycaster(),
  loaderAnim = document.querySelector('.loading');

var red_wall = 0xbc2727;
var yellow = 0xf2b233;
var green = 0x1f6652;
var white = 0xffffff;
var black = 0x000000;

// .is-mobile posée par templates/schedule/index.html.twig avant le 1er
// rendu (même pattern que /music et /story) : scène 3D illisible en petit,
// on ne l'initialise même pas, le contenu (schedule.css) reste affiché en
// flux normal.
if (!document.documentElement.classList.contains('is-mobile')) {
  init();
  animate();
}

// Indépendant du 3D (comme Player.js sur /music) : tourne dans les deux
// modes, desktop et mobile.
initMiniCalendar();

function initMiniCalendar() {
  var grid = document.getElementById('mini-calendar-grid');
  var title = document.querySelector('.mini-calendar__title');
  var dataEl = document.getElementById('schedule-events-data');
  var labelsEl = document.getElementById('schedule-month-labels');
  if (!grid || !title || !dataEl || !labelsEl) {
    return;
  }

  var eventsByDate = JSON.parse(dataEl.textContent || '{}');
  var monthLabels = JSON.parse(labelsEl.textContent || '{}');
  var eventMonths = Object.keys(eventsByDate)
    .map(function (d) {
      return d.slice(0, 7);
    })
    .sort();

  var today = new Date();
  var todayStr = toDateKey(today);
  var current = new Date(today.getFullYear(), today.getMonth(), 1);

  // Si l'URL pointe déjà sur un mois précis (#month-09, lien envoyé par
  // quelqu'un, retour en arrière du navigateur...), ouvrir directement
  // dessus plutôt que de revenir au mois du jour.
  var hashMatch = location.hash.match(/^#month-(\d{2})$/);
  if (hashMatch) {
    var hashMonth = eventMonths.filter(function (m) {
      return m.slice(5, 7) === hashMatch[1];
    })[0];
    if (hashMonth) {
      var hashParts = hashMonth.split('-').map(Number);
      current = new Date(hashParts[0], hashParts[1] - 1, 1);
    }
  } else if (eventMonths.indexOf(toDateKey(current).slice(0, 7)) === -1) {
    // Sinon, si le mois courant n'a aucun événement mais qu'il y en a plus
    // tard, ouvrir directement dessus plutôt que sur une grille vide
    // (comportement "agenda pro" classique).
    var nextMonthWithEvents = eventMonths.filter(function (m) {
      return m >= toDateKey(current).slice(0, 7);
    })[0];
    if (nextMonthWithEvents) {
      var parts = nextMonthWithEvents.split('-').map(Number);
      current = new Date(parts[0], parts[1] - 1, 1);
    }
  }

  function pad(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function toDateKey(date) {
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
  }

  function render() {
    var year = current.getFullYear();
    var month = current.getMonth();
    var monthKey = pad(month + 1);

    title.textContent = (monthLabels[monthKey] || monthKey) + ' ' + year;

    grid.innerHTML = '';

    var firstDay = new Date(year, month, 1);
    var startOffset = (firstDay.getDay() + 6) % 7; // lundi = 0, comme l'entête L M M J V S D
    var daysInMonth = new Date(year, month + 1, 0).getDate();

    for (var i = 0; i < startOffset; i++) {
      var empty = document.createElement('span');
      empty.className = 'mini-calendar__day mini-calendar__day--empty';
      grid.appendChild(empty);
    }

    for (var d = 1; d <= daysInMonth; d++) {
      var dateKey = year + '-' + monthKey + '-' + pad(d);
      var hasEvents = Object.prototype.hasOwnProperty.call(eventsByDate, dateKey);
      var cell = document.createElement(hasEvents ? 'a' : 'span');
      cell.className = 'mini-calendar__day';
      cell.textContent = String(d);

      if (dateKey === todayStr) {
        cell.classList.add('mini-calendar__day--today');
      }
      if (hasEvents) {
        cell.classList.add('mini-calendar__day--event');
        cell.href = '#month-' + monthKey;
        cell.title = eventsByDate[dateKey].join(', ');
      }

      grid.appendChild(cell);
    }
  }

  document.querySelector('[data-nav="prev"]').addEventListener('click', function () {
    current = new Date(current.getFullYear(), current.getMonth() - 1, 1);
    render();
  });
  document.querySelector('[data-nav="next"]').addEventListener('click', function () {
    current = new Date(current.getFullYear(), current.getMonth() + 1, 1);
    render();
  });

  render();
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
  scene.fog = new THREE.Fog(0xe0e0e0, 20, 100);

  clock = new THREE.Clock();
  camera = new THREE.PerspectiveCamera(52, window.innerWidth / window.innerHeight, 0.1, 100);
  camera.position.set(0, 4.3, 2);
  camera.rotateY(-Math.PI / 2);

  // lights
  var light = new THREE.HemisphereLight(white, yellow, 0.7);
  scene.add(light);

  light = new THREE.DirectionalLight(white, 0.5);
  light.position.set(-6, 2, 0);
  scene.add(light);

  light = new THREE.DirectionalLight(white, 0.05);
  light.position.set(6, 2, 0);
  scene.add(light);

  // room
  roomGeo(20, 10, 2);

  // flyer back
  var video = document.getElementById('video1');
  video.play();
  var texture = new THREE.VideoTexture(video);
  texture.minFilter = THREE.LinearFilter;
  texture.magFilter = THREE.LinearFilter;
  texture.format = THREE.RGBFormat;
  texture.encoding = THREE.sRGBEncoding;

  var flyerBack = new THREE.Mesh(
    new THREE.PlaneBufferGeometry(2.3, 3),
    new THREE.MeshToonMaterial({
      color: 0xffffff,
      map: texture,
    }),
  );
  flyerBack.position.set(9.9, 6.5, -5);
  flyerBack.rotateY(-Math.PI / 2);
  flyerBack.name = 'joinUS';
  scene.add(flyerBack);

  // model

  var loader = new GLTFLoader();
  loader.load(
    window.BB_ASSETS['mascotte/Binioufou_Final4.gltf'],
    function (gltf) {
      model = gltf.scene;
      let fileAnimations = gltf.animations;
      model.scale.set(0.45, 0.45, 0.45);
      model.position.set(9.1, 0, 5);
      model.rotateY(-Math.PI / 2);
      scene.add(model);

      mixer = new THREE.AnimationMixer(model);
      let idleAnim = THREE.AnimationClip.findByName(fileAnimations, 'walkturn2');
      let nextAnim = THREE.AnimationClip.findByName(fileAnimations, 'knocked2');
      idle = mixer.clipAction(idleAnim);
      next = mixer.clipAction(nextAnim);
      idle.play();
    },
    undefined,
    function (e) {
      console.error(e);
    },
  );

  // desk
  var loader = new GLTFLoader();
  loader.load(
    window.BB_ASSETS['story/Desk1.gltf'],
    function (gltf) {
      model = gltf.scene;
      model.scale.set(0.5, 0.5, 0.5);
      model.position.set(6, 0.1, -6);
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
  window.addEventListener('click', (e) => raycast(e));
  window.addEventListener('touchend', (e) => raycast(e, true));
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
  planeRight.name = 'planeRight';
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
//

function raycast(e, touch = false) {
  var mouse = {};
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
    if (object.name === 'planeRight') {
      if (!currentlyAnimating) {
        currentlyAnimating = true;
        playModifierAnimation(idle, 0.25, next, 0.25);
      }
    } else if (object.name === 'joinUS') {
      location.href = '/join';
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
  renderer.render(scene, camera);
}
