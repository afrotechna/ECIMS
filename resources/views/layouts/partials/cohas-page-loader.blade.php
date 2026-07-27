<div id="cohas-page-loader" class="cohas-page-loader" role="status" aria-live="polite" aria-label="Loading">
    <div class="cohas-page-loader-inner">
        <div class="cohas-page-loader-logo-wrap">
            <div class="cohas-page-loader-ring"></div>
            <div class="cohas-page-loader-logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
            </div>
        </div>
        <p class="cohas-page-loader-label">Loading…</p>
    </div>
</div>
<style>
    .cohas-page-loader {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(4px);
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }
    .cohas-page-loader.is-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    .cohas-page-loader-inner {
        text-align: center;
    }
    .cohas-page-loader-logo-wrap {
        position: relative;
        width: 108px;
        height: 108px;
        margin: 0 auto 1rem;
    }
    .cohas-page-loader-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 3px solid rgba(13, 54, 81, 0.12);
        border-top-color: #0d3651;
        animation: cohas-loader-spin 0.9s linear infinite;
    }
    .cohas-page-loader-logo {
        position: absolute;
        inset: 10px;
        border-radius: 50%;
        overflow: hidden;
        background: #fff;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 24px rgba(10, 22, 40, 0.12);
    }
    .cohas-page-loader-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center 32%;
        display: block;
    }
    .cohas-page-loader-label {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }
    @keyframes cohas-loader-spin {
        to { transform: rotate(360deg); }
    }
</style>
