/* SK Bento - cảnh 3D tấm ốp LED nội thất ở đầu trang chủ */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';

/* Đường viền + độ cong lấy từ file CAD thật (Xpander 22-25: ốp cửa trước, cửa sau, taplo) */
const PROFILES = {"door":{"outline":[[-0.4259,-0.0661],[-0.412,-0.0655],[-0.3981,-0.0649],[-0.3842,-0.0643],[-0.3703,-0.0637],[-0.3564,-0.0631],[-0.3425,-0.0625],[-0.3286,-0.0625],[-0.3147,-0.0625],[-0.3009,-0.0632],[-0.287,-0.0639],[-0.2731,-0.0646],[-0.2592,-0.0653],[-0.2453,-0.0663],[-0.2314,-0.0673],[-0.2175,-0.0683],[-0.2036,-0.0686],[-0.1897,-0.0689],[-0.1759,-0.0692],[-0.162,-0.0695],[-0.1481,-0.0703],[-0.1342,-0.0712],[-0.1203,-0.072],[-0.1064,-0.0728],[-0.0925,-0.0736],[-0.0786,-0.0744],[-0.0647,-0.0752],[-0.0509,-0.0752],[-0.037,-0.0759],[-0.0231,-0.0764],[-0.0092,-0.077],[0.0047,-0.0776],[0.0186,-0.0781],[0.0325,-0.0787],[0.0464,-0.0792],[0.0603,-0.0791],[0.0741,-0.0791],[0.088,-0.0791],[0.1019,-0.0794],[0.1158,-0.0796],[0.1297,-0.08],[0.1436,-0.0804],[0.1575,-0.0808],[0.1714,-0.0812],[0.1853,-0.0817],[0.1991,-0.0818],[0.213,-0.0817],[0.2269,-0.0815],[0.2408,-0.0811],[0.2547,-0.0805],[0.2686,-0.0799],[0.2825,-0.079],[0.2964,-0.0781],[0.3103,-0.0775],[0.3241,-0.0769],[0.338,-0.0764],[0.3519,-0.0751],[0.3658,-0.0729],[0.3797,-0.0698],[0.3936,-0.0657],[0.4075,-0.0605],[0.4214,-0.0541],[0.4353,-0.0467],[0.4491,-0.0391],[0.463,-0.0317],[0.4769,-0.0253],[0.4908,-0.02],[0.5047,-0.0157],[0.5186,-0.0125],[0.5325,-0.0105],[0.5464,-0.0095],[0.5603,-0.0095],[0.5603,0.0042],[0.5464,0.0037],[0.5325,0.0031],[0.5186,0.0023],[0.5047,0.0015],[0.4908,0.0007],[0.4769,-0.0001],[0.463,-0.0008],[0.4491,-0.0014],[0.4353,-0.0019],[0.4214,-0.0024],[0.4075,-0.0027],[0.3936,-0.0029],[0.3797,-0.0031],[0.3658,-0.0031],[0.3519,-0.0031],[0.338,-0.003],[0.3241,-0.0028],[0.3103,-0.0025],[0.2964,-0.002],[0.2825,-0.0014],[0.2686,-0.0006],[0.2547,0.0003],[0.2408,0.0013],[0.2269,0.0023],[0.213,0.0034],[0.1991,0.0046],[0.1853,0.0059],[0.1714,0.0074],[0.1575,0.0089],[0.1436,0.0106],[0.1297,0.0124],[0.1158,0.0144],[0.1019,0.0165],[0.088,0.0187],[0.0741,0.0212],[0.0603,0.0237],[0.0464,0.0264],[0.0325,0.0293],[0.0186,0.0323],[0.0047,0.0353],[-0.0092,0.0385],[-0.0231,0.0418],[-0.037,0.0453],[-0.0509,0.0489],[-0.0647,0.0529],[-0.0786,0.0571],[-0.0925,0.0613],[-0.1064,0.0652],[-0.1203,0.0678],[-0.1342,0.0687],[-0.1481,0.0676],[-0.162,0.0649],[-0.1759,0.06],[-0.1897,0.0535],[-0.2036,0.0457],[-0.2175,0.0374],[-0.2314,0.0287],[-0.2453,0.0214],[-0.2592,0.0154],[-0.2731,0.01],[-0.287,0.0038],[-0.3009,-0.0034],[-0.3147,-0.0114],[-0.3286,-0.0205],[-0.3425,-0.0285],[-0.3564,-0.0346],[-0.3703,-0.0387],[-0.3842,-0.0437],[-0.3981,-0.0507],[-0.412,-0.0571],[-0.4259,-0.0632]],"z":[-0.00487,0.02455,0.04131,-0.16451,0.11745,0.32786,2.47638],"thick":0.0499,"w":0.1583,"fitErr":0.00702,"mm":389.1},"rear":{"outline":[[-0.5721,-0.0443],[-0.5582,-0.042],[-0.5443,-0.0396],[-0.5305,-0.0373],[-0.5166,-0.035],[-0.5027,-0.0325],[-0.4888,-0.03],[-0.4749,-0.0264],[-0.461,-0.0227],[-0.4471,-0.019],[-0.4332,-0.0154],[-0.4193,-0.0117],[-0.4055,-0.0094],[-0.3916,-0.0072],[-0.3777,-0.0072],[-0.3638,-0.0072],[-0.3499,-0.0072],[-0.336,-0.0072],[-0.3221,-0.0072],[-0.3082,-0.0072],[-0.2527,-0.0121],[-0.2388,-0.0169],[-0.2249,-0.0226],[-0.211,-0.0282],[-0.1693,-0.0338],[-0.1555,-0.0395],[-0.1416,-0.0438],[-0.1277,-0.0407],[-0.1138,-0.0376],[-0.0999,-0.0337],[-0.086,-0.0298],[-0.0721,-0.0259],[-0.0443,-0.0219],[0.0807,-0.0193],[0.0945,-0.0193],[0.1084,-0.0193],[0.1223,-0.0193],[0.1362,-0.0193],[0.1501,-0.0193],[0.164,-0.0193],[0.1779,-0.0217],[0.1918,-0.0248],[0.2057,-0.0269],[0.2195,-0.0283],[0.2334,-0.0283],[0.2473,-0.0274],[0.2612,-0.0262],[0.2751,-0.0227],[0.289,-0.0185],[0.3029,-0.0152],[0.3168,-0.0127],[0.3307,-0.0116],[0.3445,-0.0113],[0.3584,-0.0113],[0.3723,-0.0113],[0.3862,-0.0113],[0.3862,0.0015],[0.3723,0.0011],[0.3584,0.0003],[0.3445,-0.0005],[0.3307,-0.0013],[0.3168,-0.002],[0.3029,-0.0026],[0.289,-0.0029],[0.2751,-0.0033],[0.2612,-0.0035],[0.2473,-0.0041],[0.2334,-0.0048],[0.2195,-0.0057],[0.2057,-0.0062],[0.1918,-0.0065],[0.1779,-0.0062],[0.164,-0.0057],[0.1501,-0.0049],[0.1362,-0.0043],[0.1223,-0.0038],[0.1084,-0.0031],[0.0945,-0.0027],[0.0807,-0.0017],[-0.0443,0.0],[-0.0721,0.0032],[-0.086,0.0067],[-0.0999,0.0109],[-0.1138,0.0149],[-0.1277,0.0187],[-0.1416,0.0221],[-0.1555,0.0257],[-0.1693,0.03],[-0.211,0.0344],[-0.2249,0.0389],[-0.2388,0.0426],[-0.2527,0.0457],[-0.3082,0.0474],[-0.3221,0.0483],[-0.336,0.0464],[-0.3499,0.0422],[-0.3638,0.0353],[-0.3777,0.0272],[-0.3916,0.0183],[-0.4055,0.0112],[-0.4193,0.0062],[-0.4332,0.0025],[-0.4471,-0.0019],[-0.461,-0.0072],[-0.4749,-0.0134],[-0.4888,-0.0206],[-0.5027,-0.028],[-0.5166,-0.0335],[-0.5305,-0.0374],[-0.5443,-0.0405],[-0.5582,-0.0424],[-0.5721,-0.0433]],"z":[-0.00901,-0.0235,0.15157,0.26941,-0.02037,-0.17685,-2.50864],"thick":0.0287,"w":0.1422,"fitErr":0.00385,"mm":435.3},"taplo":{"outline":[[-0.5544,0.0213],[-0.5405,0.0213],[-0.5266,0.0213],[-0.5127,0.0213],[-0.4988,0.0179],[-0.4849,0.0127],[-0.471,0.0076],[-0.4572,0.0025],[-0.4433,-0.0025],[-0.4294,-0.0074],[-0.4155,-0.0119],[-0.4016,-0.013],[-0.3877,-0.0123],[-0.3738,-0.0117],[-0.3599,-0.0112],[-0.346,-0.0108],[-0.3322,-0.0104],[-0.3183,-0.0104],[-0.3044,-0.0104],[-0.2905,-0.0104],[-0.2766,-0.0104],[-0.2627,-0.0104],[-0.2488,-0.0104],[-0.2349,-0.0105],[-0.221,-0.0107],[-0.2072,-0.0112],[-0.1933,-0.0116],[-0.1794,-0.0121],[-0.1655,-0.0121],[-0.1516,-0.0122],[-0.1377,-0.0121],[-0.1238,-0.0119],[-0.1099,-0.0115],[-0.096,-0.0111],[-0.0822,-0.0107],[-0.0683,-0.0107],[-0.0544,-0.0107],[-0.0405,-0.0107],[-0.0266,-0.0107],[-0.0127,-0.0107],[0.0012,-0.0107],[0.0151,-0.0095],[0.029,-0.0083],[0.0428,-0.0071],[0.0567,-0.0058],[0.0706,-0.0046],[0.0845,-0.0033],[0.0984,-0.002],[0.1123,-0.0019],[0.1262,-0.0019],[0.1401,-0.0018],[0.154,-0.0017],[0.1678,-0.0016],[0.1817,-0.0016],[0.1956,-0.0016],[0.2095,-0.0016],[0.2234,-0.0016],[0.2373,-0.0016],[0.2512,-0.0016],[0.2651,-0.0016],[0.279,-0.0017],[0.2928,-0.0018],[0.3067,-0.0016],[0.3206,-0.0015],[0.3345,-0.0013],[0.3484,-0.0011],[0.3623,-0.0009],[0.3762,-0.0006],[0.3901,-0.0004],[0.404,-0.0004],[0.4178,-0.0004],[0.4317,-0.0004],[0.4317,0.0511],[0.4178,0.0509],[0.404,0.0505],[0.3901,0.0502],[0.3762,0.05],[0.3623,0.0498],[0.3484,0.0496],[0.3345,0.0495],[0.3206,0.0493],[0.3067,0.0491],[0.2928,0.0489],[0.279,0.0489],[0.2651,0.0489],[0.2512,0.0489],[0.2373,0.049],[0.2234,0.049],[0.2095,0.049],[0.1956,0.049],[0.1817,0.0491],[0.1678,0.0491],[0.154,0.0491],[0.1401,0.0491],[0.1262,0.0491],[0.1123,0.049],[0.0984,0.049],[0.0845,0.049],[0.0706,0.0489],[0.0567,0.0489],[0.0428,0.0489],[0.029,0.0488],[0.0151,0.0488],[0.0012,0.0487],[-0.0127,0.0486],[-0.0266,0.0486],[-0.0405,0.0485],[-0.0544,0.0484],[-0.0683,0.0483],[-0.0822,0.0482],[-0.096,0.0482],[-0.1099,0.0481],[-0.1238,0.048],[-0.1377,0.0479],[-0.1516,0.0478],[-0.1655,0.0477],[-0.1794,0.0475],[-0.1933,0.0474],[-0.2072,0.0473],[-0.221,0.0472],[-0.2349,0.047],[-0.2488,0.0469],[-0.2627,0.0468],[-0.2766,0.0466],[-0.2905,0.0464],[-0.3044,0.0463],[-0.3183,0.0461],[-0.3322,0.046],[-0.346,0.0458],[-0.3599,0.0456],[-0.3738,0.0453],[-0.3877,0.0451],[-0.4016,0.0448],[-0.4155,0.0447],[-0.4294,0.044],[-0.4433,0.043],[-0.4572,0.0412],[-0.471,0.0401],[-0.4849,0.0392],[-0.4988,0.0393],[-0.5127,0.0394],[-0.5266,0.0403],[-0.5405,0.0404],[-0.5544,0.0403]],"z":[-0.00437,0.00693,0.02347,-0.03988,0.01547,-0.13611,2.17492],"thick":0.0311,"w":0.1418,"fitErr":0.00295,"mm":438.9}};

const hero = document.querySelector('.hero2');
const canvas = document.getElementById('hero3d');
if (hero && canvas) {
  try { start(); } catch (e) { console.warn('SK 3D off:', e); }
}

function start() {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
  renderer.setClearColor(0x000000, 0);
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.0;

  const scene = new THREE.Scene();
  const pmrem = new THREE.PMREMGenerator(renderer);
  scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
  scene.environmentIntensity = 0.9;

  const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 100);
  camera.position.set(0, 0.4, 11);

  /* ---------- Màu LED ---------- */
  const PALETTES = {
    rainbow: ['#F49B3F', '#DF1C4E', '#8B2BB8'],
    tim: ['#B14CFF', '#7A2CFF', '#D06BFF'],
    xanh: ['#3FA9FF', '#1E5BFF', '#5FE3FF'],
    do: ['#FF2D55', '#FF5A36', '#C8102E'],
    trang: ['#FFE9D0', '#FFFFFF', '#FFD6A6']
  };
  const uniforms = {
    uTime: { value: 0 },
    uC1: { value: new THREE.Color(PALETTES.rainbow[0]) },
    uC2: { value: new THREE.Color(PALETTES.rainbow[1]) },
    uC3: { value: new THREE.Color(PALETTES.rainbow[2]) },
    uFlow: { value: 1 },
    uGain: { value: 1.7 }
  };
  const ledMat = (offset, gain) => new THREE.ShaderMaterial({
    uniforms: Object.assign({}, uniforms, { uOff: { value: offset }, uG: { value: gain } }),
    vertexShader: 'varying vec2 vUv; void main(){ vUv=uv; gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0); }',
    fragmentShader: [
      'uniform float uTime; uniform float uFlow; uniform float uGain; uniform float uOff; uniform float uG;',
      'uniform vec3 uC1; uniform vec3 uC2; uniform vec3 uC3; varying vec2 vUv;',
      'vec3 grad(float t){ t=fract(t); return t<0.333 ? mix(uC1,uC2,t*3.0) : (t<0.666 ? mix(uC2,uC3,(t-0.333)*3.0) : mix(uC3,uC1,(t-0.666)*3.0)); }',
      'void main(){',
      '  float t = vUv.x*1.2 + uOff - uTime*0.12*uFlow;',
      '  vec3 c = grad(t);',
      '  float edge = smoothstep(0.0,0.06,vUv.x)*smoothstep(1.0,0.94,vUv.x);',
      '  gl_FragColor = vec4(c*uGain*uG*edge, 1.0);',
      '}'
    ].join('\n'),
    toneMapped: false
  });

  /* ---------- Vật liệu tấm ốp ---------- */
  const shellMat = new THREE.MeshPhysicalMaterial({ color: 0x2b2b33, metalness: 0.55, roughness: 0.28, clearcoat: 1, clearcoatRoughness: 0.18 });
  const pianoMat = new THREE.MeshPhysicalMaterial({ color: 0x121218, metalness: 0.2, roughness: 0.12, clearcoat: 1, clearcoatRoughness: 0.05 });
  const leatherMat = new THREE.MeshStandardMaterial({ color: 0x2a1a20, metalness: 0.05, roughness: 0.85 });

  const bend = (geo, amount, axis) => {
    const p = geo.attributes.position;
    for (let i = 0; i < p.count; i++) {
      const x = p.getX(i);
      p.setZ(i, p.getZ(i) - amount * x * x + (axis || 0) * x);
    }
    p.needsUpdate = true;
    geo.computeVertexNormals();
    return geo;
  };

  const root = new THREE.Group();
  scene.add(root);
  const key = new THREE.DirectionalLight(0xffffff, 1.4);
  key.position.set(-4, 6, 8);
  scene.add(key);
  const fill = new THREE.PointLight(0xDF1C4E, 18, 12, 2);
  fill.position.set(2, 0, 3);
  root.add(fill);

  /* Dựng tấm ốp từ đường viền thật */
  const panelMat = (span) => {
    const m = new THREE.MeshPhysicalMaterial({ color: 0x1c1c22, metalness: 0.35, roughness: 0.3, clearcoat: 1, clearcoatRoughness: 0.12, emissive: 0x000000 });
    m.onBeforeCompile = (sh) => {
      sh.uniforms.uTime = uniforms.uTime; sh.uniforms.uC1 = uniforms.uC1; sh.uniforms.uC2 = uniforms.uC2; sh.uniforms.uC3 = uniforms.uC3;
      sh.uniforms.uFlow = uniforms.uFlow; sh.uniforms.uSpan = { value: span };
      sh.vertexShader = 'varying float vX; varying float vZn;\n' + sh.vertexShader.replace('#include <begin_vertex>', '#include <begin_vertex>\n vX = position.x; vZn = normal.z;');
      sh.fragmentShader = 'uniform float uTime; uniform float uFlow; uniform float uSpan; uniform vec3 uC1; uniform vec3 uC2; uniform vec3 uC3; varying float vX; varying float vZn;\n'
        + 'vec3 gradF(float t){ t=fract(t); return t<0.333 ? mix(uC1,uC2,t*3.0) : (t<0.666 ? mix(uC2,uC3,(t-0.333)*3.0) : mix(uC3,uC1,(t-0.666)*3.0)); }\n'
        + sh.fragmentShader.replace('#include <emissivemap_fragment>', '#include <emissivemap_fragment>\n totalEmissiveRadiance += gradF(vX/uSpan*1.2 - uTime*0.12*uFlow) * 0.12 * smoothstep(0.2, 0.9, vZn);');
    };
    return m;
  };
  const buildPanel = (p, len, thick) => {
    const g = new THREE.Group();
    const pts = p.outline.map((q) => new THREE.Vector2(q[0] * len, q[1] * len));
    const shape = new THREE.Shape(pts);
    const geo = new THREE.ExtrudeGeometry(shape, { depth: thick, bevelEnabled: true, bevelThickness: thick * 0.45, bevelSize: thick * 0.4, bevelSegments: 3, curveSegments: 1 });
    geo.translate(0, 0, -thick);
    const c = p.z;
    const zf = (x, y) => len * (c[0] + c[1] * x + c[2] * x * x + c[3] * x * x * x + c[4] * y + c[5] * x * y + c[6] * y * y);
    const pos = geo.attributes.position;
    for (let i = 0; i < pos.count; i++) {
      const x = pos.getX(i) / len, y = pos.getY(i) / len;
      pos.setZ(i, pos.getZ(i) + zf(x, y));
    }
    pos.needsUpdate = true;
    geo.computeVertexNormals();
    g.add(new THREE.Mesh(geo, panelMat(len)));
    // dải LED dọc mép trên
    const n = p.outline.length / 2;
    const top = p.outline.slice(n).reverse();
    const led = top.filter((q, i) => i % 2 === 0).map((q) => {
      const x = q[0], y = q[1] - 0.006;
      return new THREE.Vector3(x * len, y * len, zf(x, y) + thick * 0.55);
    });
    g.add(new THREE.Mesh(new THREE.TubeGeometry(new THREE.CatmullRomCurve3(led), 220, thick * 0.32, 10, false), ledMat(0.0, 1.0)));
    return g;
  };

  /* 1) Ốp cửa trước Xpander */
  const door = buildPanel(PROFILES.door, 6.6, 0.1);
  door.position.set(0.2, 0.9, 0);
  door.rotation.set(0.1, -0.3, 0.02);
  root.add(door);

  /* 2) Ốp loa cánh cửa: đĩa tròn + vòng LED */
  const spk = new THREE.Group();
  {
    const disc = new THREE.CylinderGeometry(0.72, 0.78, 0.14, 64);
    disc.rotateX(Math.PI / 2);
    spk.add(new THREE.Mesh(disc, pianoMat));
    const grill = new THREE.CylinderGeometry(0.5, 0.5, 0.05, 64);
    grill.rotateX(Math.PI / 2); grill.translate(0, 0, 0.07);
    spk.add(new THREE.Mesh(grill, leatherMat));
    const ring = new THREE.TorusGeometry(0.62, 0.03, 12, 128);
    ring.translate(0, 0, 0.09);
    spk.add(new THREE.Mesh(ring, ledMat(0.6, 1.0)));
    const ring2 = new THREE.TorusGeometry(0.38, 0.012, 8, 96);
    ring2.translate(0, 0, 0.1);
    spk.add(new THREE.Mesh(ring2, ledMat(0.2, 0.5)));
  }
  spk.position.set(2.5, -0.9, 0.8);
  spk.rotation.set(0.1, -0.5, 0);
  root.add(spk);

  /* 3) Ốp cửa sau và ốp taplo Xpander */
  const rear = buildPanel(PROFILES.rear, 6.0, 0.09);
  rear.position.set(-0.3, -0.25, -0.6);
  rear.rotation.set(0.08, -0.26, -0.01);
  root.add(rear);
  const dash = buildPanel(PROFILES.taplo, 6.2, 0.09);
  dash.position.set(-0.6, -1.35, -1.3);
  dash.rotation.set(0.12, -0.22, -0.02);
  root.add(dash);

  /* Quầng sáng dưới chân */
  const glowTex = (() => {
    const c = document.createElement('canvas'); c.width = c.height = 128;
    const x = c.getContext('2d'); const gr = x.createRadialGradient(64, 64, 0, 64, 64, 64);
    gr.addColorStop(0, 'rgba(255,255,255,0.55)'); gr.addColorStop(1, 'rgba(255,255,255,0)');
    x.fillStyle = gr; x.fillRect(0, 0, 128, 128);
    return new THREE.CanvasTexture(c);
  })();
  const glowMat = new THREE.SpriteMaterial({ map: glowTex, color: new THREE.Color(PALETTES.rainbow[1]), transparent: true, opacity: 0.16, depthWrite: false, blending: THREE.AdditiveBlending });
  const glow = new THREE.Sprite(glowMat);
  glow.scale.set(9, 3.2, 1);
  glow.position.set(0.3, -2.2, -1.5);
  root.add(glow);

  /* ---------- Hậu kỳ: phát sáng ---------- */
  const composer = new EffectComposer(renderer);
  composer.addPass(new RenderPass(scene, camera));
  const bloom = new UnrealBloomPass(new THREE.Vector2(512, 512), 0.75, 0.45, 0.35);
  composer.addPass(bloom);
  composer.addPass(new OutputPass());

  /* ---------- Bố cục theo màn hình ---------- */
  let mobile = false;
  const resize = () => {
    const w = canvas.clientWidth, h = canvas.clientHeight;
    if (!w || !h) return;
    mobile = w / h < 1.3;
    const pr = Math.min(window.devicePixelRatio || 1, mobile ? 1.5 : 2);
    renderer.setPixelRatio(pr);
    renderer.setSize(w, h, false);
    composer.setPixelRatio(pr);
    composer.setSize(w, h);
    camera.aspect = w / h;
    if (mobile) {
      camera.position.set(0, 0.2, 18);
      root.position.set(-0.45, 3.1, 0);
      root.scale.setScalar(0.56);
    } else {
      camera.position.set(0, 0.4, 14);
      root.position.set(w / h > 1.7 ? 3.6 : 3.0, 0.2, 0);
      root.scale.setScalar(w / h > 1.7 ? 0.92 : 0.74);
    }
    camera.updateProjectionMatrix();
  };
  window.addEventListener('resize', resize);
  resize();

  /* ---------- Chuột / chạm ---------- */
  const target = { x: 0, y: 0 };
  hero.addEventListener('pointermove', (e) => {
    const r = hero.getBoundingClientRect();
    target.x = ((e.clientX - r.left) / r.width - 0.5) * 2;
    target.y = ((e.clientY - r.top) / r.height - 0.5) * 2;
  });

  /* ---------- Đổi màu ---------- */
  const setPalette = (key) => {
    const p = PALETTES[key] || PALETTES.rainbow;
    uniforms.uC1.value.set(p[0]); uniforms.uC2.value.set(p[1]); uniforms.uC3.value.set(p[2]);
    uniforms.uFlow.value = key === 'rainbow' ? 1 : 0.35;
    glowMat.color.set(p[1]);
    fill.color.set(p[1]);
    document.querySelectorAll('.led-swatch').forEach((b) => b.setAttribute('aria-pressed', b.dataset.led === key ? 'true' : 'false'));
    if (reduce) render(0);
  };
  document.querySelectorAll('.led-swatch').forEach((b) => b.addEventListener('click', () => setPalette(b.dataset.led)));

  /* ---------- Vòng lặp ---------- */
  let visible = true, last = performance.now(), t = 0;
  new IntersectionObserver((en) => { visible = en[0].isIntersecting; if (visible) loop(); }, { threshold: 0 }).observe(hero);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) loop(); });

  const render = (dt) => {
    t += dt;
    uniforms.uTime.value = t;
    const sway = reduce ? 0 : Math.sin(t * 0.25) * 0.12;
    root.rotation.y += ((sway + target.x * 0.18) - root.rotation.y) * 0.05;
    root.rotation.x += ((target.y * 0.08) - root.rotation.x) * 0.05;
    if (!reduce) {
      door.position.y = 0.9 + Math.sin(t * 0.8) * 0.06;
      rear.position.y = -0.25 + Math.sin(t * 0.75 + 0.6) * 0.05;
      spk.position.y = -0.9 + Math.sin(t * 0.9 + 1.2) * 0.08;
      spk.rotation.z = Math.sin(t * 0.3) * 0.15;
      dash.position.y = -1.35 + Math.sin(t * 0.7 + 2.1) * 0.05;
    }
    composer.render();
  };
  let running = false;
  const loop = () => {
    if (running) return;
    running = true;
    last = performance.now();
    const tick = (now) => {
      if (!visible || document.hidden) { running = false; return; }
      const dt = Math.min(0.05, (now - last) / 1000); last = now;
      render(dt);
      if (reduce) { running = false; return; }
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };

  render(0);
  hero.classList.add('has-3d');
  loop();
}
