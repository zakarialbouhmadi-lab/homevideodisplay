{**
 * Home Video Display Template - Enhanced with playlist and responsive text options
 *
 * @author Zaki LB
 * @version 1.3.0
 *}

{assign var="showTextMobile" value=$show_text_mobile}
{assign var="showTextDesktop" value=$show_text_desktop}
{assign var="hasText" value=($video_text|strip_tags|trim|strlen > 0)}

<div class="homevideo-container">
    <div class="container">
        <div class="row align-items-center {if !$showTextMobile}homevideo-hide-text-mobile{/if} {if !$showTextDesktop}homevideo-hide-text-desktop{/if}">
            
            {* Video Column *}
            <div class="col-12 {if $hasText && ($showTextMobile || $showTextDesktop)}col-md-6{else}col-md-12{/if} homevideo-video-col">
                <div class="video-wrapper">
                    <video 
                        id="homevideo-player-{$smarty.now}"
                        class="homevideo-player"
                        {if $video_autoplay}autoplay{/if}
                        {if $video_muted}muted{/if}
                        playsinline
                        controls
                        preload="metadata"
                        data-loop="{if $video_loop}true{else}false{/if}">
                        
                        {* First video source *}
                        {if isset($video_playlist[0])}
                            <source src="{$video_playlist[0].url|escape:'html':'UTF-8'}" type="video/{$video_playlist[0].type|escape:'html':'UTF-8'}">
                        {/if}
                        
                        {l s='Your browser does not support the video tag.' mod='homevideodisplay'}
                    </video>
                    
                    {* Playlist controls (if more than 1 video) *}
                    {if count($video_playlist) > 1}
                        <div class="video-playlist-controls">
                            <div class="playlist-info">
                                <span class="current-video">1</span> / <span class="total-videos">{count($video_playlist)}</span>
                            </div>
                            <div class="playlist-buttons">
                                <button type="button" class="btn-prev" title="{l s='Previous video' mod='homevideodisplay'}">‹</button>
                                <button type="button" class="btn-next" title="{l s='Next video' mod='homevideodisplay'}">›</button>
                            </div>
                        </div>
                    {/if}
                </div>
            </div>
            
            {* Text Column - conditionally displayed *}
            {if $hasText}
                <div class="col-12 {if $showTextMobile || $showTextDesktop}col-md-6{else}d-none{/if} homevideo-text-col">
                    <div class="text-content">
                        {$video_text nofilter}
                    </div>
                </div>
            {/if}
            
        </div>
    </div>
</div>

{* Video playlist data and enhanced JavaScript *}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Video playlist data
    var videoPlaylist = [
        {foreach from=$video_playlist item=video}
            {
                url: '{$video.url|escape:'javascript':'UTF-8'}',
                type: '{$video.type|escape:'javascript':'UTF-8'}',
                filename: '{$video.filename|escape:'javascript':'UTF-8'}'
            }{if !$video@last},{/if}
        {/foreach}
    ];
    
    var currentVideoIndex = 0;
    var shouldLoop = {if $video_loop}true{else}false{/if};
    var shouldAutoplay = {if $video_autoplay}true{else}false{/if};
    
    var video = document.querySelector('#homevideo-player-{$smarty.now}');
    var currentVideoSpan = document.querySelector('.current-video');
    var btnPrev = document.querySelector('.btn-prev');
    var btnNext = document.querySelector('.btn-next');
    
    if (!video || videoPlaylist.length === 0) {
        return;
    }
    
    // Function to load video at specific index
    function loadVideo(index) {
        if (index < 0 || index >= videoPlaylist.length) {
            return;
        }
        
        currentVideoIndex = index;
        var videoData = videoPlaylist[index];
        
        // Update video source
        video.src = videoData.url;
        video.load();
        
        // Update playlist info
        if (currentVideoSpan) {
            currentVideoSpan.textContent = index + 1;
        }
        
        // Auto-play if needed
        if (shouldAutoplay) {
            video.play().catch(function(error) {
                console.log('Autoplay prevented:', error);
            });
        }
    }
    
    // Function to play next video
    function playNext() {
        if (currentVideoIndex < videoPlaylist.length - 1) {
            loadVideo(currentVideoIndex + 1);
        } else if (shouldLoop) {
            loadVideo(0);
        }
    }
    
    // Function to play previous video
    function playPrevious() {
        if (currentVideoIndex > 0) {
            loadVideo(currentVideoIndex - 1);
        } else if (shouldLoop) {
            loadVideo(videoPlaylist.length - 1);
        }
    }
    
    // Event listeners
    video.addEventListener('ended', function() {
        if (videoPlaylist.length > 1) {
            setTimeout(playNext, 500); // Small delay between videos
        } else if (shouldLoop) {
            video.play();
        }
    });
    
    video.addEventListener('error', function() {
        console.error('Error loading video:', videoPlaylist[currentVideoIndex]);
        // Try next video if available
        if (currentVideoIndex < videoPlaylist.length - 1) {
            setTimeout(playNext, 1000);
        }
    });
    
    // Playlist control buttons
    if (btnNext) {
        btnNext.addEventListener('click', playNext);
    }
    
    if (btnPrev) {
        btnPrev.addEventListener('click', playPrevious);
    }
    
    // Keyboard controls
    document.addEventListener('keydown', function(e) {
        if (video === document.activeElement || video.parentElement.contains(document.activeElement)) {
            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    playPrevious();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    playNext();
                    break;
            }
        }
    });
    
    // Enhanced autoplay handling
    if (shouldAutoplay && videoPlaylist.length > 0) {
        // Wait a bit for the page to fully load
        setTimeout(function() {
            video.play().catch(function(error) {
                console.log('Autoplay prevented, user interaction required:', error);
                
                // If autoplay fails, show a play button overlay
                var playButton = document.createElement('div');
                playButton.className = 'video-play-overlay';
                playButton.innerHTML = '<button type="button" class="play-btn">▶ ' + '{l s="Play Video" mod="homevideodisplay"}' + '</button>';
                playButton.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; background: rgba(0,0,0,0.7); color: white; padding: 15px 25px; border-radius: 5px; cursor: pointer; font-size: 16px;';
                
                video.parentElement.style.position = 'relative';
                video.parentElement.appendChild(playButton);
                
                playButton.addEventListener('click', function() {
                    video.play();
                    playButton.remove();
                });
            });
        }, 100);
    }
    
    // Intersection Observer for better autoplay handling
    if ('IntersectionObserver' in window && shouldAutoplay) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && video.paused) {
                    video.play().catch(function(error) {
                        console.log('Autoplay on scroll prevented:', error);
                    });
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(video);
    }
});
</script>
