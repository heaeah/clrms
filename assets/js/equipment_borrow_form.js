const canvas = document.getElementById('signature-pad');
const signaturePad = new SignaturePad(canvas);
const clearButton = document.getElementById('clear-signature');
const hiddenInput = document.getElementById('signature_image');

document.querySelector('form').addEventListener('submit', function (e) {
    if (!signaturePad.isEmpty()) {
        hiddenInput.value = signaturePad.toDataURL();
    } else {
        alert("Please provide a signature before submitting.");
        e.preventDefault();
    }
});

clearButton.addEventListener('click', function () {
    signaturePad.clear();
    hiddenInput.value = '';
}); 