
// Create a WaveSurfer instance
var wavesurfer;

// Init on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    wavesurfer = WaveSurfer.create({
        container: '#waveform',
        waveColor: '#e2e2e2',
        progressColor: '#ffffff',
        cursorColor: '#ffffff',
        barWidth: 1,
        height: 91,
        //backend: 'MediaElement',
        plugins: [
            WaveSurfer.regions.create()
        ]
    });
});

document.querySelector('#slider').oninput = function () {
    wavesurfer.zoom(Number(this.value));
};


// Bind controls
document.addEventListener('DOMContentLoaded', function () {

    var playPause = document.querySelector('#playPause');
    playPause.addEventListener('click', function () {
        wavesurfer.playPause();
    });

    var stop = document.querySelector('#stop');
    stop.addEventListener('click', function () {
        wavesurfer.stop();
    });

    // Toggle play/pause text
    wavesurfer.on('play', function () {
        document.querySelector('#play').style.display = 'none';
        document.querySelector('#pause').style.display = '';
    });
    wavesurfer.on('pause', function () {
        document.querySelector('#play').style.display = '';
        document.querySelector('#pause').style.display = 'none';
    });

    var loopRegion = document.querySelector('#loopRegion');
    loopRegion.addEventListener('click', function () {

        if (hasClass(loopRegion, 'looping')) {
            loopRegion.classList.remove('looping');
            wavesurfer.clearRegions();
            //wavesurfer.play();
        } else {
            wavesurfer.clearRegions();
            loopRegion.classList.add('looping');
            wavesurfer.addRegion({
                id: 'loop',
                start: 5,
                end: 25,
                loop: false,
                color: 'hsla(163, 53%, 26%, 0.4)'
            });
            wavesurfer.regions.list['loop'].playLoop();
            //var region = wavesurfer.regions.list['loopMe'];
            //region.playLoop();
        }
    });


    // The playlist links
    var links = document.querySelectorAll('#playlist a');
    var currentTrack = 0;

    // Load a track by index and highlight the corresponding link
    var setCurrentSong = function (index) {
        links[currentTrack].classList.remove('active');
        currentTrack = index;
        links[currentTrack].classList.add('active');
        wavesurfer.load(links[currentTrack].href);
    };

    // Load the track on click
    Array.prototype.forEach.call(links, function (link, index) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            setCurrentSong(index);
        });
    });

    // Play on audio load
    wavesurfer.on('ready', function () {
        wavesurfer.play();
    });

    wavesurfer.on('error', function (e) {
        console.warn(e);
    });

    // Go to the next track on finish
    wavesurfer.on('finish', function () {
        setCurrentSong((currentTrack + 1) % links.length);
    });

    // Load the first track
    setCurrentSong(currentTrack);
});


function hasClass(elem, className) {
    return new RegExp(' ' + className + ' ').test(' ' + elem.className + ' ');
}