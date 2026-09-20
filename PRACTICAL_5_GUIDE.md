# Practical 5 — Registration Form with Frontend Validation

## Files changed
- `register.html`: new form (name, email, mobile, password, confirm password, course, year, gender, role, CAPTCHA, terms). Loads `js/validation.js`.
- `js/validation.js`: NEW file. All validation logic. Loaded on `register.html` only.
- `css/style.css`: "Practical 5" section added at the end (errors, strength meter, CAPTCHA, dark-theme versions).
- `js/script.js` (Practical 4) is unchanged.

## How to demo
Open `register.html` with Live Server (or `python -m http.server 8000`, then `http://localhost:8000/register.html`).

1. Click **Create StudentHub Account** with everything empty. Ten errors appear next to their fields, a summary appears at the top, and keyboard focus moves to the first invalid field.
2. Type in each field and watch the messages change while typing (real-time validation on keyup/input/change).
3. Type in Password and watch the strength bar and text change (Very weak → Strong).
4. Click **New code** to get a new CAPTCHA.
5. Fill everything correctly and submit: a green success message appears.
6. Switch to dark mode and repeat step 1 to show the errors in dark theme.

## Validation test cases (for the report)

### Valid input
| Field | Value |
|---|---|
| Full name | Darshil Rupani |
| Email | darshil@college.edu |
| Mobile | 9876543210 |
| Password / Confirm | Abcdef1@ / Abcdef1@ |
| Course / Year | B.Tech Computer Engineering / Third year |
| Gender | Male |
| CAPTCHA | the characters shown (any case) |
| Terms | checked |
| **Expected** | Green "Registration successful" message |

### Invalid input
| # | Field | Input | Expected message |
|---|---|---|---|
| 1 | All | (empty, click submit) | "Please fix 10 errors…" plus a message under every field |
| 2 | Name | `Ra12` | Use 2 to 50 letters… numbers are not allowed |
| 3 | Email | `abc@` | Enter a valid email address… |
| 4 | Mobile | `12345` | Must have exactly 10 digits |
| 5 | Mobile | `1234567890` | Must start with 6, 7, 8 or 9 |
| 6 | Mobile | `98a4567890` | Use digits only |
| 7 | Password | `abc` | Needs at least 8 characters, an uppercase letter, a number, a symbol; strength: Very weak |
| 8 | Password | `Abcdef12` | Needs a symbol; strength: Good |
| 9 | Confirm | `Abcdef1#` (password is `Abcdef1@`) | The passwords do not match |
| 10 | Course / Year | not selected | Please select your course / year |
| 11 | Gender | none chosen | Please select your gender |
| 12 | CAPTCHA | wrong characters | The characters do not match |
| 13 | Terms | unchecked | You must accept the terms and conditions |

## Regular expressions used
| Field | Pattern | Meaning |
|---|---|---|
| Name | `/^[A-Za-z][A-Za-z .'-]{1,49}$/` | Starts with a letter; 2–50 letters, spaces, dot, apostrophe, hyphen |
| Email | `/^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/` | text, `@`, domain, dot, 2+ letters |
| Mobile | `/^[6-9][0-9]{9}$/` | 10 digits starting with 6–9 |
| Password | `/[A-Z]/`, `/[a-z]/`, `/[0-9]/`, `/[^A-Za-z0-9\s]/` plus length ≥ 8 | uppercase, lowercase, digit, symbol |

## Source-code explanation
- The form has `novalidate`, so the browser's default pop-ups are replaced by our own messages placed under each field.
- Each field has a `<label for>` and `aria-describedby` pointing to its hint and error paragraph. When invalid, JavaScript sets `aria-invalid="true"`, so screen readers announce the error with the field.
- `rules` is an array with one validator per field. Each returns an error message, or an empty string if valid.
- `keyup`, `input` and `change` listeners validate while typing; `blur` validates when leaving a field; `submit` validates everything, focuses the first invalid field and shows a summary.
- `updateStrengthMeter()` counts 5 checks (length, uppercase, lowercase, number, symbol), sets the bar width and text.
- The CAPTCHA is drawn on a `<canvas>`: random characters (from `crypto.getRandomValues`) with rotation, noise lines and dots.

## Answers to the key questions
1. **Input types and attributes:** `text`, `email`, `tel`, `password`, `radio`, `checkbox`, `select`; plus `required`, `minlength`, `maxlength`, `inputmode`, `autocomplete` and `placeholder`.
2. **Errors near fields:** each field has its own `<p class="error-message">` directly beneath it.
3. **Password strength:** yes. The meter and the error message check length, uppercase, lowercase, number and symbol.
4. **Keyboard and screen readers:** all controls are native, labelled, and reachable with Tab; errors are linked with `aria-describedby` and `aria-invalid`; the status message uses `role="status"`; focus outline is visible.

## Known limitations (mention if asked)
- Validation is client-side only. A real system must validate again on the server.
- Nothing is stored or sent; the success message is a demo.
- The canvas CAPTCHA is an image, so it is hard for screen-reader users; a real system would offer an audio alternative or use a different check.
