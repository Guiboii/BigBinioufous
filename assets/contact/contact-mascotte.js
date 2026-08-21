import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

// The same animated 3D mascot as /home, shown as a small thumbnail in the /contact header without rebuilding the room around it. Transparent background (alpha:true, no scene.background) so the character sits directly on the page's pink rather than in a frame.
var canvas = document.getElementById('contact-mascotte');

if (canvas) {
  var clock = new THREE.Clock();
  var mixer;

  var renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
  renderer.setPixelRatio(window.devicePixelRatio);
  renderer.outputEncoding = THREE.sRGBEncoding;

  var scene = new THREE.Scene();

  var camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
  camera.position.set(0, 1.9, 6);
  camera.lookAt(0, 1.4, 0);

  var white = 0xffffff;
  var yellow = 0xf2b233;

  var light = new THREE.HemisphereLight(white, yellow, 0.9);
  scene.add(light);
  light = new THREE.DirectionalLight(white, 0.6);
  light.position.set(-4, 3, 4);
  scene.add(light);
  light = new THREE.DirectionalLight(white, 0.6);
  light.position.set(4, 3, 4);
  scene.add(light);

  new GLTFLoader().load(
    window.BB_ASSETS['mascotte/Binioufou_Final4.gltf'],
    function (gltf) {
      var model = gltf.scene;
      scene.add(model);
      mixer = new THREE.AnimationMixer(model);
      var idleAnim = THREE.AnimationClip.findByName(gltf.animations, 'idle');
      mixer.clipAction(idleAnim).play();
    },
    undefined,
    function (e) {
      console.error(e);
    },
  );

  function resize() {
    var rect = canvas.getBoundingClientRect();
    renderer.setSize(rect.width, rect.height, false);
    camera.aspect = rect.width / rect.height;
    camera.updateProjectionMatrix();
  }
  window.addEventListener('resize', resize);
  resize();

  (function animate() {
    requestAnimationFrame(animate);
    var delta = clock.getDelta();
    if (mixer) {
      mixer.update(delta);
    }
    renderer.render(scene, camera);
  })();
}
