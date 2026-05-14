<div class="position-fixed" style="bottom: 70px; right: 20px; z-index: 1050;">
    <a href="https://wa.me/923335132395" target="_blank" class="btn btn-success shadow-lg rounded-circle position-relative"
       style="width: 45px; height: 45px;">
        <i class="bi bi-whatsapp" style="font-size: 20px;"></i>
    </a>
</div>

<div class="position-fixed" style="bottom: 20px; right: 20px; z-index: 1050;">
    <button id="videoGuideBtn" class="btn btn-dark shadow-lg rounded-circle position-relative"
            style="width: 45px; height: 45px;"
            data-bs-toggle="modal" data-bs-target="#videoGuideModal">
        <i class="bi bi-play-circle-fill" style="font-size: 20px;"></i>
    </button>
</div>

<!-- Video Guide Modal -->
<div class="modal fade" id="videoGuideModal" tabindex="-1" aria-labelledby="videoGuideLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoGuideLabel">How to Install the App</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <iframe id="videoFrame" width="100%" height="400" src="{{ asset('videos/guideVideo.mp4') }}"
                        title="Installation Guide" frameborder="0" allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>
</div>
