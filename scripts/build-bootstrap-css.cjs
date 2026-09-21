const fs = require('fs');
const path = require('path');
const { PurgeCSS } = require('purgecss');

const root = path.resolve(__dirname, '..');
const source = path.join(root, 'public/vendor/bootstrap/5.3.3/bootstrap.min.css');
const destination = path.join(root, 'public/css/bootstrap-app.min.css');

const content = [
    path.join(root, 'resources/views/**/*.blade.php'),
    path.join(root, 'resources/js/**/*.{js,jsx}'),
    path.join(root, 'public/js/employee-attendance.js'),
    path.join(root, 'vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php'),
];

const safelist = {
    standard: [
        'active', 'disabled', 'fade', 'show', 'hide', 'collapsing',
        'modal-open', 'modal-backdrop', 'was-validated',
        'is-valid', 'is-invalid', 'valid-feedback', 'invalid-feedback',
    ],
    greedy: [
        /^modal(?:-|$)/,
        /^alert(?:-|$)/,
        /^collapse(?:-|$)/,
        /^dropdown(?:-|$)/,
        /^offcanvas(?:-|$)/,
        /^tooltip(?:-|$)/,
        /^popover(?:-|$)/,
        /^bs-/,
    ],
};

(async () => {
    if (!fs.existsSync(source)) {
        throw new Error(`Bootstrap source not found: ${source}`);
    }

    const [result] = await new PurgeCSS().purge({
        content,
        css: [source],
        defaultExtractor: value => value.match(/[A-Za-z0-9-_:/]+/g) || [],
        safelist,
        keyframes: false,
        fontFace: false,
        variables: false,
    });

    fs.mkdirSync(path.dirname(destination), { recursive: true });
    fs.writeFileSync(destination, result.css);

    const before = fs.statSync(source).size;
    const after = Buffer.byteLength(result.css);
    const reduction = (((before - after) / before) * 100).toFixed(1);
    process.stdout.write(`Bootstrap CSS: ${before} -> ${after} bytes (${reduction}% smaller)\n`);
})().catch(error => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exitCode = 1;
});
