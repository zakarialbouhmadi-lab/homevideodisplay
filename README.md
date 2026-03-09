# Home Video Display - PrestaShop Module
![PrestaShop](https://img.shields.io/badge/PrestaShop-8-blue)

## Overview

**Home Video Display** (v1.3.0) is a robust PrestaShop module designed to showcase a sequence of videos directly on the homepage. It features a built-in playlist system, configurable video controls, and multi-language text areas that can be toggled independently for mobile and desktop devices.

<img width="1253" height="609" alt="image" src="https://github.com/user-attachments/assets/c0196655-4eb2-4fcc-8f31-c6dd9b3494be" />


## Technical Stack & Requirements

**PrestaShop Compatibility:** 8.0.0 and higher.

**Supported Video Formats:** MP4, WebM, and OGG.
 
**Maximum File Size:** 100MB per video.


## Key Features
 
**Video Playlist:** Upload multiple videos that will automatically play in a sequence. Includes front-end navigation controls (Next/Previous).
 
**Advanced Playback Controls:** Toggle Autoplay, Loop, and Muted states directly from the back office.
 
**Responsive Multi-language Text:** Add descriptive text next to the video player with full multi-language support.
 
**Device-Specific Visibility:** Independently show or hide the text content on Mobile and Desktop views to optimize the user experience.
 
**Smart Autoplay:** Utilizes JavaScript `IntersectionObserver` to trigger autoplay only when the video scrolls into the user's viewport, saving bandwidth and improving page load performance.

---

## Installation

1. Download the module as a `.zip` file.
2. Navigate to your PrestaShop Back Office: **Modules > Module Manager**.
3. Click on **Upload a module** and select the `.zip` archive.
4. Once uploaded, click **Install**. The module will automatically create the necessary upload directories and register its hooks.

## Configuration & Usage

Once installed, click **Configure** on the module.

### 1. General Settings
 
**Enable module:** Toggle the visibility of the entire block on the homepage.
 
**Autoplay:** Set the video to play automatically. (Note: Most modern browsers require videos to be 'Muted' for autoplay to function ).
 
**Loop playlist:** When the last video finishes, it will restart from the first video.

**Muted:** Mutes the video audio by default.


<img width="1147" height="723" alt="image" src="https://github.com/user-attachments/assets/5b0bacf8-5b22-4b6d-8c84-037f9df842b5" />

### 2. Device Visibility

Control where your multi-language text appears:

**Show text on mobile:** Yes/No.

**Show text on desktop:** Yes/No.



### 3. Video Management

* Use the **Upload Video** field to add new clips.


* The **Current Videos** table displays all uploaded files in their playback order.


* You can **View** or **Delete** existing videos directly from this table.



<img width="992" height="543" alt="image" src="https://github.com/user-attachments/assets/cbefdb49-2187-4c32-8701-61b74156ad6c" />

### 4. Text Content

* Add rich text, HTML, or links using the WYSIWYG editor.


* Use the language selector to provide translations for all active store languages.



<img width="962" height="452" alt="image" src="https://github.com/user-attachments/assets/27a723bb-cee4-412a-9df6-e7dea377fbc3" />

---

## Technical Details for Developers

### Hooks Utilized

`displayHome`: Renders the main template (`homevideo.tpl`) and the video player logic.
 
`actionFrontControllerSetMedia`: Injects the custom stylesheet (`front.css`) exclusively on the `index` controller to prevent unnecessary CSS loading on other pages.



### Database & Storage

**Configuration:** Settings are stored in the `ps_configuration` table under keys prefixed with `HOMEVIDEO_` (e.g., `HOMEVIDEO_VIDEOS`, `HOMEVIDEO_AUTOPLAY`).
 
**File Storage:** Uploaded videos are securely stored in `/modules/homevideodisplay/views/videos/`. The directory is protected via a `.htaccess` file that blocks directory listing and strictly allows only `.mp4`, `.webm`, and `.ogg` files.


### Uninstallation

Uninstalling the module will cleanly remove all associated `Configuration` keys and physically delete all uploaded video files from the server, preventing orphaned data.
