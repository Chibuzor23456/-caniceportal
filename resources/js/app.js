import sort from '@alpinejs/sort';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import SignaturePad from 'signature_pad';

document.addEventListener('alpine:init', () => {
    Alpine.plugin(sort);
});

// Exposed globally so inline x-data expressions (one per dynamically added
// quotation section / signature pad) can instantiate them directly without
// needing a named Alpine.data() component per instance.
window.Quill = Quill;
window.SignaturePad = SignaturePad;
