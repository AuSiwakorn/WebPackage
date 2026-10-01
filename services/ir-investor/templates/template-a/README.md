# AOINVESTOR IR Template — Demo (A / B / C)

หน้า HTML ชุดเดียว (7 หน้า) แสดงได้ 3 template โดยไม่แก้ HTML — สลับด้วยปุ่ม A / B / C มุมล่างซ้าย

## โครงสร้างไฟล์

| ไฟล์ | หน้าที่ |
|---|---|
| `assets/ir-core.css` | โครงสร้างและ component ทุกตัว (header, IR nav, hero, stock card, KPI, ตาราง, ฟอร์ม …) ไม่มีสี/ฟอนต์/มุมโค้งตายตัว ทุกอย่างอ้าง CSS token |
| `assets/ir-themes.css` | ค่า token ของแต่ละ template + การจัดวาง hero ที่ต่างกัน ครอบด้วย `[data-template="a|b|c"]` |
| `assets/ir-demo.js` | ตัวสลับ template สำหรับไฟล์สาธิตเท่านั้น (จำค่าไว้ใน localStorage) — **ไม่ต้องใส่ในระบบจริง** |
| `index.html` … `contact.html` | หน้า demo ทั้ง 7 หน้า ข้อมูลสมมติทั้งหมด |

## Template ทั้ง 3 แบบ

| | A — Dashboard | B — Classic Banner | C — Executive Dark |
|---|---|---|---|
| Hero | ข้อความซ้าย + Stock card ขวา | แบนเนอร์เต็มกว้าง + แถบราคาหุ้นลอยทับขอบล่าง | โทนเข้ม + Stock card สีเข้มขอบทอง |
| ฟอนต์ | IBM Plex Sans Thai | Sarabun (หนา 700) | เนื้อหา IBM Plex Sans Thai / หัวข้อ Noto Serif Thai |
| มุมโค้ง | 10 / 14 px | 4 / 6 px | 2 / 4 px |
| สีตั้งต้น (demo) | น้ำเงินกรมท่า + ทอง | เขียวองค์กร + ทอง | กรมท่าเกือบดำ + ทอง |
| Pagebar / IR nav | พื้นอ่อน / สี CI | พื้นอ่อน / CI เข้ม | พื้นเข้ม / เข้ม |

โครงสร้างข้อมูลและ component เหมือนกันทุก template — เปลี่ยน template ภายหลังได้โดยไม่กระทบเนื้อหา

## การใช้กับ Admweb (ระบบจริง)

1. render `<html lang="th" data-template="{ir_setting.template}">` — ค่า `a` / `b` / `c`
2. โหลด `ir-core.css` แล้วตามด้วย `ir-themes.css`
3. สี CI ของลูกค้า ใส่ทับ token ต่อท้าย (inline `<style>` หรือไฟล์ที่ระบบสร้าง) โดยไม่ต้องแก้ theme:
   ```css
   :root{--ci:#8B0000;--ci-700:#5E0000;--ci-400:#B03030;--ci-50:#F8ECEC;--accent:#C9A86A}
   ```
   token ที่ลูกค้าปรับได้: `--ci` `--ci-700` `--ci-400` `--ci-50` `--accent` `--accent-ink` (ที่เหลือมาจาก theme)
4. ไม่ต้องใส่ `ir-demo.js` และ `.demo-bar`
5. header / footer ของลูกค้า (`.cust-*`) render จาก `ir_setting.header_html` / `footer_html` ตาม spec

## Token หลักใน ir-core.css

`--ci --ci-700 --ci-400 --ci-50 --accent --accent-ink --on-ci-muted --link` (สี) ·
`--font-body --font-head --head-weight` (ฟอนต์) ·
`--radius --radius-lg --radius-btn --card-shadow` (รูปทรง) ·
`--hero-bg --hero-ink --hero-muted --hero-pattern` (hero) ·
`--chart --chart-2 --chart-3 --chart-4` (สีกราฟ)

Stock card ในหน้าแรกมี token ย่อยของตัวเอง (`--sc-bg --sc-ink --sc-muted --sc-line --sc-border`) และ pagebar มี `--pb-bg --pb-ink --pb-muted` เพื่อให้ theme ปรับเฉพาะส่วนนั้นได้

## หมายเหตุ

- `assets/ir.css` เดิมถูกแทนด้วย `ir-core.css` + `ir-themes.css` แล้ว ลบทิ้งได้
- ยังเป็น static demo: ยังไม่มี partial (header/nav/footer ซ้ำในทุกไฟล์), TH/EN เป็นปุ่มเปล่า, ยังไม่ผูก Admweb
