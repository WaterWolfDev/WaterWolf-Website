// Frontend styling
import "~/scss/wwradio.scss";

// Frontend JS
import $ from "jquery";
window.jQuery = window.$ = $;

const ready = (callback) => {
    if (document.readyState !== "loading") callback();
    else document.addEventListener("DOMContentLoaded", callback);
};

const infoUrl = "https://quasar.shoutca.st:2199/external/rpc.php?m=streaminfo.get&username=techsane";

function updateStreamInfo(){
    $.getJSON(infoUrl).done(function(data){
        const radioData = data.data[0] ?? {};
        if (!radioData.offline) {
            $("#d_online").text("Online")
            $("#d_adjonline").text(radioData.autodj)
            $("#d_source").text(radioData.source)
            $("#d_listeners").text(radioData.listeners + " Out Of " + radioData.maxlisteners)
            $("#d_date").text(radioData.date)
            $("#d_time").text(radioData.time)
            $("#d_song").text(radioData.song)
            $("#d_song_raw").text(radioData.rawmeta)
            $("#d_stype").text(radioData.servertype)
            $("#d_listenurl").text(radioData.tuneinurl).attr("href", radioData.tuneinurl)
        }
    });
}

window.startInfoLoop = function() {
    let countdown = 10;
    updateStreamInfo();
    setInterval(function(){
        if(countdown === 0) {
            updateStreamInfo();
            countdown = 10;

            $("#rf-countdown").text(countdown+"...Refreshed!")
        } else {
            countdown = countdown - 1
            $("#rf-countdown").text(countdown)
        }
    },1000)
};

//  Delta Radio Player 2. Developed for Delta Radio By Jamie Overington (Jamie Overington.co.uk)
//  Canvas Visualizations

let nowPlayingImage = "";
let nowPlayingArtist = "";
let nowPlayingTitle = "";

function findUrl(image, size) {
    let n, entry;
    try{
        for (n = 0; n < image.length; ++n) {
            entry = image[n];
            if (entry.size === size) {
                return entry["#text"];
            }
        }
        return "";
    }
    catch(e){
        return "";
    }
}

const streamInfoUrl = "https://quasar.shoutca.st:2199/external/rpc.php?m=streaminfo.get&username=techsane";
const lastFmApiKey = "791f0afe347adc2ba93a3c7317a2810a";

function GetCurrentTrack(){
    $.getJSON(streamInfoUrl).done(function(data) {
        const radioData = data.data[0] ?? {};

        if (nowPlayingArtist === radioData.track.artist || nowPlayingTitle === radioData.track.title) {
            return;
        }

        nowPlayingArtist = radioData.track.artist;
        nowPlayingTitle = radioData.track.title;

        $('#np-artist').text(nowPlayingArtist);
        $('#np-title').text(nowPlayingTitle);

        if (nowPlayingArtist.length > 0 && nowPlayingTitle.length > 0) {
            let lastFmUrl = "https://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key=" + lastFmApiKey
                + "&autocorrect=1&artist=" + encodeURIComponent(nowPlayingArtist) + "&autocorrect=1&track="
                + encodeURIComponent(nowPlayingTitle) + "&format=json";

            $.getJSON(lastFmUrl).done(function (data) {
                console.log("Fetching Track...")
                try {
                    nowPlayingImage = findUrl(data.track.album.image, "large")
                    if (nowPlayingImage.length > 0) {
                        $('#np-album-art-box').css("background-image", "url('" + nowPlayingImage + "')");
                        $('.background-box').css("background-image", "url('" + nowPlayingImage + "')");
                        console.log("Found Track!")
                    }
                } catch (e) {
                    lastFmUrl = "https://ws.audioscrobbler.com/2.0/?method=artist.getInfo&api_key=" + lastFmApiKey
                        + "&autocorrect=1&artist=" + encodeURIComponent(nowPlayingArtist) + "&format=json";

                    $.getJSON(lastFmUrl).done(function (data) {
                        nowPlayingImage = findUrl(data.artist.image, "large")
                        if (nowPlayingImage.length > 0) {
                            $('#np-album-art-box').css("background-image", "url('" + nowPlayingImage + "')");
                            $('.background-box').css("background-image", "url('" + nowPlayingImage + "')");
                            console.log("Couldn't Find track, But found artist.", nowPlayingArtist, nowPlayingTitle)
                        } else {
                            console.log("Couldnt Find artist", nowPlayingArtist)
                            $('#np-album-art-box').css("background-image", " url('files/img/no-art.png')");
                            $('.background-box').css("background-image", " url('files/img/no-art.png')");
                            console.log("---[ Album Art Grabber ]---")
                            console.log("Unable to find album art on last.fm")
                            console.log("--- ------------------- ---")
                        }
                    });
                }
            });
        }
    });
}

window.startPlayerLoop = function() {
    setTimeout(function(){
        $("video").prop("muted",false).prop("volume",0.5);
    }, 1000);

    GetCurrentTrack();
    setTimeout(function(){
        $('#np-artist').text(nowPlayingArtist);
        $('#np-title').text(nowPlayingTitle);
    },3000)
};
