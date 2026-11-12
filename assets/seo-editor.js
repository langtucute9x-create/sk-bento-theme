/* Ô SEO trong trình soạn bài (SK Bento) — không cần build, dùng wp.element trực tiếp */
(function (wp) {
  if (!wp || !wp.plugins || !wp.data || !wp.element) return;
  var el = wp.element.createElement, useState = wp.element.useState;
  var C = wp.components, useSelect = wp.data.useSelect, useDispatch = wp.data.useDispatch;
  var Panel = (wp.editor && wp.editor.PluginDocumentSettingPanel) || (wp.editPost && wp.editPost.PluginDocumentSettingPanel);
  if (!Panel) return;
  var CFG = window.SKB_SEO || { site: '', home: '/', host: '', sep: ' – ' };

  /* bỏ dấu tiếng Việt để so từ khoá với đường dẫn, không phân biệt hoa thường */
  function bo(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().replace(/\s+/g, ' ').trim(); }
  function chua(hay, kw) { return kw && bo(hay).indexOf(bo(kw)) >= 0; }
  /* đủ các chữ của từ khoá nhưng không liền nhau */
  function duChu(hay, kw) { var h = ' ' + bo(hay).replace(/[^a-z0-9]+/g, ' ') + ' '; return !!kw && bo(kw).split(' ').every(function (w) { return h.indexOf(' ' + w.replace(/[^a-z0-9]/g, '') + ' ') >= 0; }); }
  function slugTu(s) { return bo(s).replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); }
  function dem(hay, kw) { var h = bo(hay), k = bo(kw), n = 0, i = 0; if (!k) return 0; while ((i = h.indexOf(k, i)) >= 0) { n++; i += k.length; } return n; }

  function phanTich(o) {
    var doc = new DOMParser().parseFromString('<div>' + (o.html || '') + '</div>', 'text/html');
    var text = (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
    var words = text ? text.split(' ').length : 0;
    var heads = Array.prototype.map.call(doc.querySelectorAll('h2,h3,h4'), function (h) { return h.textContent; });
    var h2 = doc.querySelectorAll('h2').length;
    var imgs = doc.querySelectorAll('img'), noAlt = Array.prototype.filter.call(imgs, function (i) { return !(i.getAttribute('alt') || '').trim(); }).length;
    var links = Array.prototype.map.call(doc.querySelectorAll('a[href]'), function (a) { return a.getAttribute('href'); });
    var noiBo = links.filter(function (h) { return /^\//.test(h) || (CFG.host && h.indexOf(CFG.host) >= 0); }).length;
    var kw = (o.kw || '').trim(), kwN = kw ? bo(kw).split(' ').length : 0;
    var lan = dem(text, kw), matDo = words ? (lan * kwN * 100 / words) : 0;
    var t = o.title.length, d = o.desc.length;
    var R = [];
    function r(ok, warn, msg) { R.push({ s: ok ? 'tot' : (warn ? 'vua' : 'kem'), m: msg }); }
    if (!kw) {
      R.push({ s: 'kem', m: 'Chưa nhập từ khoá chính — nhập cụm khách hay gõ trên Google, ví dụ "led nội thất CRV".' });
    } else {
      r(chua(o.title, kw), duChu(o.title, kw), chua(o.title, kw) ? 'Từ khoá có trong tiêu đề SEO.' : duChu(o.title, kw) ? 'Tiêu đề có đủ các chữ của từ khoá nhưng chưa liền nhau — viết liền cụm "' + kw + '" sẽ tốt hơn.' : 'Đưa từ khoá vào tiêu đề SEO (càng gần đầu càng tốt).');
      r(chua(o.desc, kw), duChu(o.desc, kw), chua(o.desc, kw) ? 'Từ khoá có trong mô tả.' : duChu(o.desc, kw) ? 'Mô tả có đủ các chữ của từ khoá nhưng chưa liền nhau.' : 'Đưa từ khoá vào mô tả.');
      r(chua(o.slug.replace(/-/g, ' '), kw), duChu(o.slug.replace(/-/g, ' '), kw), chua(o.slug.replace(/-/g, ' '), kw) ? 'Từ khoá có trong đường dẫn.' : 'Đường dẫn chưa có từ khoá (sửa ở mục Đường dẫn bên phải).');
      r(chua(text.slice(0, 300), kw), true, chua(text.slice(0, 300), kw) ? 'Từ khoá xuất hiện ngay đoạn đầu.' : 'Nhắc từ khoá trong 1–2 câu đầu bài.');
      var hk = heads.some(function (h) { return chua(h, kw); });
      r(hk, true, hk ? 'Có tiêu đề phụ chứa từ khoá.' : 'Thêm từ khoá vào ít nhất một tiêu đề phụ (H2/H3).');
      if (words >= 100) r(matDo >= 0.5 && matDo <= 3, matDo > 0, 'Từ khoá lặp ' + lan + ' lần (' + matDo.toFixed(1) + '%)' + (matDo < 0.5 ? ' — hơi ít, nên 0,5–3%.' : matDo > 3 ? ' — quá dày, Google dễ coi là nhồi từ.' : ' — vừa phải.'));
    }
    r(t >= 30 && t <= 60, t > 0 && t <= 70, 'Tiêu đề SEO ' + t + ' ký tự' + (t < 30 ? ' — hơi ngắn, nên 30–60.' : t > 60 ? ' — dài, Google có thể cắt bớt (nên ≤ 60).' : ' — vừa đẹp.'));
    r(d >= 120 && d <= 160, d >= 70 && d <= 180, o.descTu ? 'Chưa viết mô tả — đang lấy tạm đoạn đầu bài. Nên tự viết 120–160 ký tự có lời mời gọi.' : 'Mô tả ' + d + ' ký tự' + (d < 120 ? ' — hơi ngắn, nên 120–160.' : d > 160 ? ' — dài, Google sẽ cắt bớt.' : ' — vừa đẹp.'));
    if (o.loai !== 'page') r(words >= 300, words >= 150, 'Bài có ' + words + ' chữ' + (words < 300 ? ' — nên từ 300 chữ trở lên để Google hiểu nội dung.' : '.'));
    if (h2 > 0) r(true, false, 'Có ' + h2 + ' tiêu đề phụ H2.'); else if (words >= 300) r(false, false, 'Chia bài bằng tiêu đề phụ (khối Tiêu đề, cỡ H2).');
    r(!!o.anh, false, o.anh ? 'Đã có ảnh đại diện (hiện khi chia sẻ Facebook, Zalo).' : 'Chưa có ảnh đại diện — bấm "Đặt ảnh đại diện".');
    if (imgs.length) r(!noAlt, false, noAlt ? noAlt + '/' + imgs.length + ' ảnh chưa có chữ mô tả (Alt) — chọn ảnh → khung bên phải → "Văn bản thay thế".' : 'Mọi ảnh đều có chữ mô tả (Alt).');
    r(noiBo > 0, false, noiBo ? 'Có ' + noiBo + ' liên kết tới trang khác của web.' : 'Thêm ít nhất 1 liên kết tới trang mẫu xe, đặt lịch hoặc đại lý của web.');
    var diem = Math.round(R.reduce(function (a, x) { return a + (x.s === 'tot' ? 1 : x.s === 'vua' ? 0.5 : 0); }, 0) * 100 / R.length);
    return { R: R, diem: diem };
  }

  var MAU = { tot: '#1a7f37', vua: '#b26a00', kem: '#b32d2e' };

  function SeoPanel() {
    var d = useSelect(function (s) {
      var e = s('core/editor');
      return {
        meta: e.getEditedPostAttribute('meta') || {},
        title: e.getEditedPostAttribute('title') || '',
        slug: e.getEditedPostAttribute('slug') || '',
        st: e.getEditedPostAttribute('status') || '',
        link: e.getPermalink ? (e.getPermalink() || '') : '',
        anh: e.getEditedPostAttribute('featured_media'),
        loai: e.getCurrentPostType(),
        html: e.getEditedPostContent(),
        excerpt: e.getEditedPostAttribute('excerpt') || '',
      };
    }, []);
    var edit = useDispatch('core/editor').editPost;
    var m = d.meta;
    function set(k, v) { var o = {}; o[k] = v; edit({ meta: o }); }
    var tieuDe = (m._skb_seo_title || '').trim() || (d.title ? d.title + CFG.sep + CFG.site : '');
    var thanBai = (new DOMParser().parseFromString('<div>' + (d.html || '') + '</div>', 'text/html').body.textContent || '').replace(/\s+/g, ' ').trim();
    var moTaTu = !(m._skb_seo_desc || '').trim();
    var moTa = (m._skb_seo_desc || '').trim() || (d.excerpt || thanBai).slice(0, 158);
    var slug = (d.slug && d.st !== 'auto-draft') ? d.slug : slugTu(d.title);
    var kq = phanTich({ kw: m._skb_seo_kw || '', title: tieuDe, desc: moTa, descTu: moTaTu, slug: slug, html: d.html, anh: d.anh, loai: d.loai });
    var url = (d.link && d.slug && d.st !== 'auto-draft' && !/[?&]p=/.test(d.link)) ? d.link : (CFG.home + (slug ? slug + '/' : ''));
    var nhan = kq.diem >= 80 ? 'Tốt' : kq.diem >= 50 ? 'Khá' : 'Cần sửa';
    var mau = kq.diem >= 80 ? MAU.tot : kq.diem >= 50 ? MAU.vua : MAU.kem;
    var gon = useState(false);

    function dem(s, a, b) { var n = s.length; return el('span', { style: { float: 'right', color: n === 0 ? '#757575' : (n >= a && n <= b ? MAU.tot : MAU.vua), fontWeight: 600 } }, n + '/' + b); }

    return el(Panel, { name: 'skb-seo', title: 'SEO · ' + kq.diem + '/100 ' + nhan, className: 'skb-seo', initialOpen: true },
      el(C.TextControl, { label: 'Từ khoá chính', help: 'Cụm khách hay gõ trên Google, ví dụ: led nội thất Honda CRV', value: m._skb_seo_kw || '', onChange: function (v) { set('_skb_seo_kw', v); }, __nextHasNoMarginBottom: true }),
      el('div', { style: { height: 12 } }),
      el(C.TextControl, { label: el('span', null, 'Tiêu đề SEO ', dem(m._skb_seo_title || '', 30, 60)), placeholder: d.title ? d.title + CFG.sep + CFG.site : '', help: 'Để trống thì dùng tiêu đề bài.', value: m._skb_seo_title || '', onChange: function (v) { set('_skb_seo_title', v); }, __nextHasNoMarginBottom: true }),
      el('div', { style: { height: 12 } }),
      el(C.TextareaControl, { label: el('span', null, 'Mô tả trên Google ', dem(m._skb_seo_desc || '', 120, 160)), placeholder: 'Để trống thì lấy đoạn đầu bài', rows: 4, value: m._skb_seo_desc || '', onChange: function (v) { set('_skb_seo_desc', v); }, __nextHasNoMarginBottom: true }),
      el('p', { style: { margin: '12px 0 6px', fontWeight: 600 } }, 'Xem trước trên Google'),
      el('div', { style: { border: '1px solid #e0e0e0', borderRadius: 8, padding: '10px 12px', background: '#fff', fontFamily: 'Arial, sans-serif' } },
        el('div', { style: { fontSize: 12, color: '#202124', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' } }, url.replace(/^https?:\/\//, '').replace(/\/$/, '').split('/').join(' › ')),
        el('div', { style: { fontSize: 17, lineHeight: 1.3, color: '#1a0dab', margin: '3px 0', overflow: 'hidden', display: '-webkit-box', WebkitLineClamp: 1, WebkitBoxOrient: 'vertical' } }, tieuDe.length > 62 ? tieuDe.slice(0, 60) + '…' : (tieuDe || '(chưa có tiêu đề)')),
        el('div', { style: { fontSize: 13, lineHeight: 1.45, color: '#4d5156' } }, moTa.length > 160 ? moTa.slice(0, 157) + '…' : (moTa || '(chưa có mô tả)'))
      ),
      el('div', { style: { margin: '14px 0 6px', display: 'flex', alignItems: 'center', gap: 8 } },
        el('span', { style: { display: 'inline-block', minWidth: 44, padding: '2px 8px', borderRadius: 12, background: mau, color: '#fff', fontWeight: 700, textAlign: 'center' } }, kq.diem),
        el('strong', null, 'Chấm điểm: ' + nhan),
        el(C.Button, { variant: 'link', style: { marginLeft: 'auto' }, onClick: function () { gon[1](!gon[0]); } }, gon[0] ? 'Hiện hết' : 'Chỉ hiện việc cần sửa')
      ),
      el('ul', { style: { margin: 0, paddingLeft: 0, listStyle: 'none' } },
        kq.R.filter(function (x) { return !gon[0] || x.s !== 'tot'; }).map(function (x, i) {
          return el('li', { key: i, style: { display: 'flex', gap: 8, margin: '0 0 6px', lineHeight: 1.4 } },
            el('span', { style: { flex: '0 0 10px', height: 10, marginTop: 4, borderRadius: '50%', background: MAU[x.s] } }), el('span', null, x.m));
        })
      ),
      el('div', { style: { height: 8 } }),
      el(C.CheckboxControl, { label: 'Ẩn trang này khỏi Google (noindex)', checked: !!m._skb_seo_noindex, onChange: function (v) { set('_skb_seo_noindex', !!v); }, __nextHasNoMarginBottom: true })
    );
  }

  wp.plugins.registerPlugin('skb-seo', { render: SeoPanel, icon: null });
})(window.wp);
