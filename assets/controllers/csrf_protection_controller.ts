const nameCheck: RegExp = /^[-_a-zA-Z0-9]{4,22}$/;
const tokenCheck: RegExp = /^[-_/+a-zA-Z0-9]{24,}$/;

// Generate and double-submit a CSRF token in a form field and a cookie, as defined by Symfony's SameOriginCsrfTokenManager
document.addEventListener('submit', function (event: Event): void {
    generateCsrfToken(event.target as HTMLFormElement);
}, true);

// When @hotwired/turbo handles form submissions, send the CSRF token in a header in addition to a cookie
// The `framework.csrf_protection.check_header` config option needs to be enabled for the header to be checked
document.addEventListener('turbo:submit-start', function (event: any): void {
    const h = generateCsrfHeaders(event.detail.formSubmission.formElement);
    Object.keys(h).map(function (k: string): void {
        event.detail.formSubmission.fetchRequest.headers[k] = h[k];
    });
});

// When @hotwired/turbo handles form submissions, remove the CSRF cookie once a form has been submitted
document.addEventListener('turbo:submit-end', function (event: any): void {
    removeCsrfToken(event.detail.formSubmission.formElement);
});

export function generateCsrfToken(formElement: HTMLFormElement): void {
    const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]') as HTMLInputElement;

    if (!csrfField) {
        return;
    }

    let csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
    let csrfToken = csrfField.value;

    if (!csrfCookie && nameCheck.test(csrfToken)) {
        csrfField.setAttribute('data-csrf-protection-cookie-value', csrfCookie = csrfToken);
        const crypto = window.crypto || (window as any).msCrypto;
        csrfField.defaultValue = csrfToken = btoa(String.fromCharCode.apply(null, Array.from(crypto.getRandomValues(new Uint8Array(18)))));
        csrfField.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (csrfCookie && tokenCheck.test(csrfToken)) {
        const cookie = csrfCookie + '_' + csrfToken + '=' + csrfCookie + '; path=/; samesite=strict';
        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }
}

export function generateCsrfHeaders(formElement: HTMLFormElement): Record<string, string> {
    const headers: Record<string, string> = {};
    const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]') as HTMLInputElement;

    if (!csrfField) {
        return headers;
    }

    const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

    if (csrfCookie && tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        headers[csrfCookie] = csrfField.value;
    }

    return headers;
}

export function removeCsrfToken(formElement: HTMLFormElement): void {
    const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]') as HTMLInputElement;

    if (!csrfField) {
        return;
    }

    const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

    if (csrfCookie && tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        const cookie = csrfCookie + '_' + csrfField.value + '=0; path=/; samesite=strict; max-age=0';

        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }
}

/* stimulusFetch: 'lazy' */
export default 'csrf-protection-controller';
