@extends('layouts.app')

@section('content')
<style>
    .ticket-viewer-stage {
        --ticket-scale: 1.7;
        height: min(82vh, 920px);
        border: 1px solid #dee2e6;
        border-radius: 10px;
        overflow: auto;
        background: #f7f7f7;
        padding: 8px 10px 8px 6px;
    }

    .ticket-frame-wrap {
        width: calc((100% - 16px) / var(--ticket-scale));
        height: calc(100% / var(--ticket-scale));
        transform: scale(var(--ticket-scale));
        transform-origin: top left;
        margin-left: 4px;
    }

    .ticket-frame-wrap iframe {
        width: 100%;
        height: 100%;
        border: 0;
        background: #fff;
    }

    @media (max-width: 992px) {
        .ticket-viewer-stage {
            --ticket-scale: 1.35;
            height: min(78vh, 780px);
            padding: 6px;
        }

        .ticket-frame-wrap {
            width: calc((100% - 10px) / var(--ticket-scale));
            margin-left: 2px;
        }
    }
</style>

<div class="container-fluid py-3 py-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ url()->previous() ?: route('fichas.index') }}" class="btn btn-outline-secondary btn-lg" id="btn-back-ticket" data-back-url="{{ route('fichas.index') }}" title="{{ __('Volver') }}">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-lg" id="btn-print-ticket" data-pdf-url="{{ $pdfUrl }}" title="{{ __('Imprimir') }}">
                                <i class="bi bi-printer me-1"></i>
                            </button>
                        </div>
                    </div>
                    <div class="ticket-viewer-stage">
                        <div class="ticket-frame-wrap">
                            <iframe id="ticket-pdf-frame" src="{{ $pdfUrl }}#toolbar=1&view=FitH&zoom=320" title="Ticket PDF"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        const backBtn = document.getElementById('btn-back-ticket');
        const printBtn = document.getElementById('btn-print-ticket');
        const frame = document.getElementById('ticket-pdf-frame');
        const pdfUrl = printBtn ? printBtn.dataset.pdfUrl : '';

        if (backBtn) {
            backBtn.addEventListener('click', function() {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }

                const backUrl = backBtn.dataset.backUrl;
                if (backUrl) {
                    window.location.href = backUrl;
                }
            });
        }

        if (!printBtn) return;

        printBtn.addEventListener('click', function() {
            try {
                if (frame && frame.contentWindow) {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                    return;
                }
            } catch (e) {
                // Fallback cuando el visor embebido no permite print() en PWA
            }

            // Evita bloqueos de popups en modo standalone de PWA
            window.location.href = pdfUrl + '#toolbar=1&view=FitH&zoom=320';
        });
    })();
</script>
@endpush