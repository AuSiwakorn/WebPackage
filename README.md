# WebPackage — หน้าขายบริการของ AOSOFT

หน้า onepage สำหรับแต่ละบริการ (HTML ไฟล์เดียว ไม่มี build) แต่ละบริการอยู่ในโฟลเดอร์ของตัวเอง
ประกอบด้วย `index.html` และภาพแชร์ social (Open Graph) ขนาด 1200×630

## โครงสร้างและ URL

Repo นี้ deploy ทั้งก้อนไปที่ `https://www.aosoft.co.th/services/` — ชื่อโฟลเดอร์ = path ของ URL

| โฟลเดอร์ | บริการ | URL |
|---|---|---|
| `accessibility-tool/` | AOACCESS — ไอคอนช่วยเหลือผู้พิการ (Web Accessibility Widget) | https://www.aosoft.co.th/services/accessibility-tool/ |
| `cookie-consent/` | AOCOOKIE — Cookie Consent Banner ตาม PDPA | https://www.aosoft.co.th/services/cookie-consent/ |
| `ir-investor/` | AOINVESTOR — เว็บไซต์นักลงทุนสัมพันธ์ (IR) | https://www.aosoft.co.th/services/ir-investor/ |
| `stock-system/` | AOSTOCK — ระบบบริหารสต๊อกสินค้า (Stock Management) | https://www.aosoft.co.th/services/stock-system/ |

## กติกาการตั้งชื่อ

- ชื่อโฟลเดอร์ใช้ตัวพิมพ์เล็กและขีดกลาง (kebab-case) เพราะเป็น URL โดยตรง
- URL ใช้รูปแบบโฟลเดอร์ + slash ท้ายเสมอ (`/services/xxx/`) และ canonical ในไฟล์ต้องตรงรูปแบบนี้
- ภาพ OG ตั้งชื่อ `<brand>-og.png` วางในโฟลเดอร์เดียวกับ `index.html`

## เมื่อเพิ่มบริการใหม่

1. สร้างโฟลเดอร์ `services-name/` พร้อม `index.html` และ `xxx-og.png`
2. ในไฟล์ต้องตั้ง `canonical`, `hreflang`, `og:url`, `og:image`, `twitter:image` และ `@id`/`url` ใน JSON-LD ให้เป็น `https://www.aosoft.co.th/services/services-name/`
3. เพิ่มแถวในตารางด้านบน
4. หลัง deploy ตรวจด้วย Facebook Sharing Debugger และ Google Rich Results Test

## หมายเหตุ

- ราคาแพ็กเกจของแต่ละหน้าแก้ได้ที่ `PLANS` ท้ายไฟล์ (JS คำนวณการ์ด/ตารางให้) — แต่ต้องแก้ `meta description` และ `offers` ใน JSON-LD ให้ตรงด้วย
- `Claude outputs/` เก็บภาพ preview ที่ใช้ระหว่างออกแบบ ไม่ต้อง deploy
- `services/stock-system/demo/` เป็นระบบทดลองใช้งานฝั่งพนักงาน (PHP + session ยังไม่ต่อฐานข้อมูล)
  บัญชีทดลองอยู่ใน `demo/inc/config.php` · เข้าที่ `/services/stock-system/demo/`
