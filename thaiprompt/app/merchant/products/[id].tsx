/**
 * เพิ่ม / แก้สินค้าของร้าน — /merchant/products/new · /merchant/products/{id}
 * (กติกาเดียวกับฟอร์มเว็บ /seller/products/create|edit — server ตรวจซ้ำทุกช่อง)
 *
 * - ช่องข้อมูล: ชื่อ หมวดหมู่ ราคา ราคาก่อนลด ต้นทุน สต็อก ติดตามสต็อก คำอธิบายสั้น/ยาว การจัดส่ง
 *   รายละเอียดเพิ่มเติม (แบรนด์ น้ำหนัก ขนาด SKU) · ตรวจในจอก่อนส่ง ข้อความไทยรายช่อง
 * - รูป: เพิ่มใหม่ = เลือกในเครื่องแล้วส่งพร้อมฟอร์ม · แก้ไข = อัปโหลด/ตั้งรูปหลัก/เรียง/ลบทันที
 * - เงินที่ร้านได้รับ: คำนวณจาก PricingEngine ชุดเดียวกับตอนแบ่งเงินจริง (หน่วง 600ms หลังพิมพ์ราคา)
 * - GP/VAT แสดงอย่างเดียว (ผู้ขายตั้ง GP เองไม่ได้) · PV/เงินคืนลูกค้า เป็นฟีเจอร์บนเว็บ (คงค่าเดิม)
 * - สินค้าถูกระงับ / มีตัวเลือกย่อย → อ่านอย่างเดียว + ปุ่มแก้บนเว็บ (ไม่แตะตัวเลือกย่อยเด็ดขาด)
 * - ออกจากหน้าโดยยังไม่บันทึก / ระหว่างอัปโหลด → ถามก่อน (ออก = ยกเลิกการอัปโหลด)
 * - สร้างสินค้าส่ง Idempotency-Key เดิมตลอดอายุฟอร์ม → เน็ตหลุดแล้วกดใหม่ไม่ได้สินค้าซ้อน
 *
 * หน้าตา: แถบรูป (รูปหลักขอบทอง) → การ์ดข้อมูล → การ์ดราคา + กล่องรายได้ → สต็อก → การจัดส่ง
 *         → รายละเอียดเพิ่มเติม (พับได้) → สถานะการขาย/ลบ · ปุ่มทองบันทึกในแถบลอยท้ายจอ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, StyleSheet, View } from 'react-native';
import { router, useLocalSearchParams, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import { newIdempotencyKey, type ApiFailure } from '@/services/api/client';
import {
  addSellerProductImages,
  createSellerProduct,
  deleteSellerProduct,
  deleteSellerProductImage,
  getSellerProduct,
  getSellerProductMeta,
  quoteSellerProduct,
  reorderSellerProductImages,
  setSellerProductActive,
  setSellerProductMainImage,
  updateSellerProduct,
  type SellerProductDetail,
  type SellerProductInput,
  type SellerProductMeta,
  type SellerProductQuote,
  type SellerShippingMethod,
} from '@/services/api/sellerProductApi';
import { Button3D, Card3D, Chip, EmptyState, Icon, Pill, PriceText, Screen, WebsiteButton, resultHaptic } from '@/components/ui';
import { Field, FormSheet, SearchField, StickyBar } from '@/components/shop';
import { IconTile, NoticeBanner } from '@/components/merchant';
import {
  ErrorNote,
  FormCard,
  FormSkeleton,
  ProductImageStrip,
  SellerGateNotice,
  ToggleRow,
  choosePhotoSource,
  formatInputNumber,
  isGateFailure,
  parseInteger,
  parseMoney,
  pickImages,
  type StripImage,
} from '@/components/seller';
import { useTheme, radii, spacing, typography } from '@/theme';

// =====================================================
// ฟอร์ม
// =====================================================

type TextKey =
  | 'name'
  | 'price'
  | 'compare_at_price'
  | 'cost_price'
  | 'stock_quantity'
  | 'short_description'
  | 'description'
  | 'brand'
  | 'weight'
  | 'dimensions'
  | 'sku'
  | 'shipping_fee'
  | 'shipping_weight_kg'
  | 'free_shipping_min_amount';

interface FormState extends Record<TextKey, string> {
  category_id: number | null;
  track_inventory: boolean;
  shipping_method: SellerShippingMethod;
}

type Errors = Partial<Record<TextKey | 'category_id' | 'images', string>>;

const EMPTY_FORM: FormState = {
  name: '',
  category_id: null,
  price: '',
  compare_at_price: '',
  cost_price: '',
  stock_quantity: '0',
  track_inventory: true,
  short_description: '',
  description: '',
  brand: '',
  weight: '',
  dimensions: '',
  sku: '',
  shipping_method: 'store_default',
  shipping_fee: '50',
  shipping_weight_kg: '',
  free_shipping_min_amount: '',
};

const SHIPPING_HINT: Record<SellerShippingMethod, string> = {
  store_default: 'ใช้ค่าส่งและเกณฑ์ส่งฟรีที่ตั้งไว้ในหน้าตั้งค่าร้าน',
  free: 'ลูกค้าไม่ต้องจ่ายค่าส่งสำหรับสินค้าชิ้นนี้ (ร้านรับภาระค่าส่งเอง)',
  flat_rate: 'คิดค่าส่งคงที่ต่อชิ้น ไม่ขึ้นกับน้ำหนัก',
  weight_based: 'ระบบคำนวณค่าส่งจากน้ำหนักตามอัตรามาตรฐานของระบบ',
};

const FALLBACK_SHIPPING: { value: SellerShippingMethod; label: string }[] = [
  { value: 'store_default', label: 'ใช้ค่าเริ่มต้นของร้าน' },
  { value: 'free', label: 'ส่งฟรี' },
  { value: 'flat_rate', label: 'ค่าส่งเหมาต่อชิ้น' },
  { value: 'weight_based', label: 'คิดตามน้ำหนัก' },
];

const THAI = /[฀-๿]/;
const QUOTE_DELAY_MS = 600;
const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

const formFromProduct = (p: SellerProductDetail): FormState => ({
  name: p.name,
  category_id: p.category_id,
  price: formatInputNumber(p.price),
  compare_at_price: formatInputNumber(p.compare_at_price),
  cost_price: formatInputNumber(p.cost_price),
  stock_quantity: String(p.stock_quantity),
  track_inventory: p.track_inventory,
  short_description: p.short_description ?? '',
  description: p.description ?? '',
  brand: p.brand ?? '',
  weight: formatInputNumber(p.weight),
  dimensions: p.dimensions ?? '',
  sku: p.sku ?? '',
  shipping_method: p.shipping_method,
  shipping_fee: formatInputNumber(p.shipping_fee) || '0',
  shipping_weight_kg: formatInputNumber(p.shipping_weight_kg),
  free_shipping_min_amount: formatInputNumber(p.free_shipping_min_amount),
});

/** ค่าที่ใช้เทียบว่าแก้ฟอร์มหรือยัง */
const serialize = (f: FormState): string => JSON.stringify(f);

/** ตรวจก่อนส่ง — กฎเดียวกับ SellerProductService::rules() (+ ช่องที่วิธีส่งนั้นต้องใช้) */
const validate = (f: FormState, meta: SellerProductMeta | null, imageCount: number, isCreate: boolean): Errors => {
  const e: Errors = {};
  const maxPrice = meta?.limits.max_price ?? 10_000_000;
  const maxShip = meta?.limits.max_shipping_fee ?? 5000;
  const money = (key: TextKey, label: string, required = false, max = maxPrice) => {
    const v = parseMoney(f[key]);
    if (v === null) {
      if (required) e[key] = `กรุณากรอก${label}`;
    } else if (Number.isNaN(v)) e[key] = `${label}ต้องเป็นตัวเลข (ทศนิยมไม่เกิน 2 ตำแหน่ง)`;
    else if (v > max) e[key] = `${label}ต้องไม่เกิน ${max.toLocaleString('th-TH')}`;
  };

  const name = f.name.trim();
  if (!name) e.name = 'กรุณากรอกชื่อสินค้า';
  else if (name.length > 255) e.name = 'ชื่อสินค้าต้องไม่เกิน 255 ตัวอักษร';
  if (!f.category_id) e.category_id = 'กรุณาเลือกหมวดหมู่สินค้า';
  money('price', 'ราคาขาย', true);
  money('compare_at_price', 'ราคาก่อนลด');
  money('cost_price', 'ต้นทุน');

  const stock = parseInteger(f.stock_quantity);
  if (stock === null) e.stock_quantity = 'กรุณากรอกจำนวนในสต็อก';
  else if (Number.isNaN(stock) || stock > 1_000_000) e.stock_quantity = 'จำนวนในสต็อกต้องเป็นจำนวนเต็ม 0 – 1,000,000';

  if (f.short_description.length > 500) e.short_description = 'คำอธิบายสั้นต้องไม่เกิน 500 ตัวอักษร';
  if (f.brand.length > 100) e.brand = 'ชื่อแบรนด์ต้องไม่เกิน 100 ตัวอักษร';
  if (f.dimensions.length > 100) e.dimensions = 'ขนาดสินค้าต้องไม่เกิน 100 ตัวอักษร';
  if (f.sku.length > 100) e.sku = 'รหัสสินค้า (SKU) ต้องไม่เกิน 100 ตัวอักษร';
  money('weight', 'น้ำหนัก', false, 999_999);

  if (f.shipping_method === 'flat_rate') {
    money('shipping_fee', 'ค่าส่งต่อชิ้น', true, maxShip);
    money('free_shipping_min_amount', 'ยอดส่งฟรี');
  }
  if (f.shipping_method === 'weight_based') {
    money('shipping_weight_kg', 'น้ำหนักจัดส่ง', true, 99_999);
    money('free_shipping_min_amount', 'ยอดส่งฟรี');
  }

  if (isCreate && imageCount === 0) e.images = 'เพิ่มรูปสินค้าอย่างน้อย 1 รูป';
  return e;
};

/** ฟอร์ม → ข้อมูลที่ส่ง server (ช่องที่วิธีส่งไม่ใช้ = null) */
const toInput = (f: FormState): SellerProductInput => {
  const text = (v: string) => (v.trim() === '' ? null : v.trim());
  const money = (v: string) => {
    const n = parseMoney(v);
    return n === null || Number.isNaN(n) ? null : n;
  };
  const usesFreeThreshold = f.shipping_method === 'flat_rate' || f.shipping_method === 'weight_based';
  return {
    name: f.name.trim(),
    category_id: f.category_id ?? 0,
    price: money(f.price) ?? 0,
    compare_at_price: money(f.compare_at_price),
    cost_price: money(f.cost_price),
    stock_quantity: parseInteger(f.stock_quantity) ?? 0,
    track_inventory: f.track_inventory,
    short_description: text(f.short_description),
    description: text(f.description),
    brand: text(f.brand),
    weight: money(f.weight),
    dimensions: text(f.dimensions),
    sku: text(f.sku),
    shipping_method: f.shipping_method,
    shipping_fee: f.shipping_method === 'flat_rate' ? money(f.shipping_fee) : null,
    shipping_weight_kg: f.shipping_method === 'weight_based' ? money(f.shipping_weight_kg) : null,
    free_shipping_min_amount: usesFreeThreshold ? money(f.free_shipping_min_amount) : null,
  };
};

// =====================================================
// หน้าจอ
// =====================================================

export default function SellerProductEditorScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const params = useLocalSearchParams<{ id?: string }>();
  const isCreate = params.id === 'new';
  const productId = useMemo(() => {
    const n = Number(params.id);
    return Number.isInteger(n) && n > 0 ? n : null;
  }, [params.id]);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const title = isCreate ? 'เพิ่มสินค้าใหม่' : 'แก้ไขสินค้า';

  const [meta, setMeta] = useState<SellerProductMeta | null>(null);
  const [product, setProduct] = useState<SellerProductDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadError, setLoadError] = useState<ApiFailure | null>(null);

  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [baseline, setBaseline] = useState(serialize(EMPTY_FORM));
  const [errors, setErrors] = useState<Errors>({});
  const [saveError, setSaveError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [showMore, setShowMore] = useState(false);
  const [categorySheet, setCategorySheet] = useState(false);
  const [categorySearch, setCategorySearch] = useState('');

  // รูปในเครื่อง (โหมดเพิ่มสินค้า) — ตัวแรกคือรูปหลัก
  const [localImages, setLocalImages] = useState<string[]>([]);
  const [imageBusy, setImageBusy] = useState(false);
  const [activeBusy, setActiveBusy] = useState(false);

  /** บันทึก/ลบสำเร็จแล้ว → ปลดตัวถามก่อนออก แล้วค่อยย้อนกลับ (ต้องรอ render รอบถัดไปก่อน) */
  const [exiting, setExiting] = useState(false);

  const [quote, setQuote] = useState<SellerProductQuote | null>(null);
  const [quoteLoading, setQuoteLoading] = useState(false);

  const mountedRef = useRef(true);
  const savingRef = useRef(false);
  const leavingRef = useRef(false);
  const idempotencyKeyRef = useRef(newIdempotencyKey());
  const uploadAbortRef = useRef<AbortController | null>(null);
  const quoteAbortRef = useRef<AbortController | null>(null);
  const noticeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      uploadAbortRef.current?.abort();
      quoteAbortRef.current?.abort();
      if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    };
  }, []);

  const flash = useCallback((text: string) => {
    if (!mountedRef.current) return;
    if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    setNotice(text);
    noticeTimerRef.current = setTimeout(() => mountedRef.current && setNotice(null), 3500);
  }, []);

  /** ใส่ค่าจาก server ลงฟอร์ม (ทับการแก้ไข — ใช้ตอนโหลดครั้งแรก/หลังบันทึก) */
  const resetForm = (p: SellerProductDetail) => {
    const next = formFromProduct(p);
    setForm(next);
    setBaseline(serialize(next));
    setErrors({});
    setSaveError(null);
  };

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated || (!isCreate && !productId)) {
        setLoading(false);
        return;
      }
      if (mode === 'refresh') setRefreshing(true);
      const [metaRes, productRes] = await Promise.all([
        getSellerProductMeta(),
        isCreate || !productId ? Promise.resolve(null) : getSellerProduct(productId),
      ]);
      if (!mountedRef.current) return;
      setLoading(false);
      setRefreshing(false);

      if (!metaRes.success) {
        if (mode === 'initial' || isGateFailure(metaRes)) setLoadError(metaRes);
        else Alert.alert('รีเฟรชไม่สำเร็จ', metaRes.message);
        return;
      }
      setMeta(metaRes.data);

      if (productRes) {
        if (!productRes.success) {
          if (mode === 'initial' || productRes.status === 404) setLoadError(productRes);
          else Alert.alert('รีเฟรชไม่สำเร็จ', productRes.message);
          return;
        }
        setProduct(productRes.data);
        resetForm(productRes.data);
      }
      setLoadError(null);
    },
    [isAuthenticated, isCreate, productId]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  const readOnly = !!product?.read_only;
  const dirty = !readOnly && (serialize(form) !== baseline || (isCreate && localImages.length > 0));

  // ---------- ออกจากหน้าโดยยังไม่บันทึก / ระหว่างอัปโหลด → ถามก่อน ----------
  usePreventRemove((dirty || saving || imageBusy) && !exiting && !leavingRef.current, ({ data }) => {
    const uploading = saving || imageBusy;
    Alert.alert(
      uploading ? 'กำลังบันทึกอยู่' : 'ยังไม่ได้บันทึก',
      uploading
        ? 'ออกจากหน้านี้ตอนนี้ การอัปโหลดจะถูกยกเลิก และข้อมูลอาจยังไม่ถูกบันทึก'
        : 'มีการแก้ไขที่ยังไม่บันทึก ออกจากหน้านี้แล้วการแก้ไขจะหายไป',
      [
        { text: 'อยู่ต่อ', style: 'cancel' },
        {
          text: uploading ? 'ยกเลิกแล้วออก' : 'ทิ้งการแก้ไข',
          style: 'destructive',
          onPress: () => {
            leavingRef.current = true;
            uploadAbortRef.current?.abort();
            navigation.dispatch(data.action);
          },
        },
      ]
    );
  });

  // ย้อนกลับหลัง render ที่ปลดตัวถามแล้ว (ถ้าย้อนทันทีจะโดนถาม "ยังไม่ได้บันทึก" เอง)
  useEffect(() => {
    if (!exiting) return;
    if (router.canGoBack()) router.back();
    else router.replace('/merchant/products' as never);
  }, [exiting]);

  const setText = (key: TextKey, value: string) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => (prev[key] ? { ...prev, [key]: undefined } : prev));
    setSaveError(null);
  };

  const setMoneyText = (key: TextKey) => (value: string) => setText(key, value.replace(/[^0-9.]/g, '').slice(0, 12));

  // ---------- เงินที่ร้านได้รับ (หน่วงหลังพิมพ์) ----------
  const priceValue = parseMoney(form.price);
  const costValue = parseMoney(form.cost_price);
  useEffect(() => {
    if (!meta || priceValue === null || Number.isNaN(priceValue) || priceValue <= 0) {
      quoteAbortRef.current?.abort();
      setQuote(null);
      setQuoteLoading(false);
      return;
    }
    const timer = setTimeout(async () => {
      quoteAbortRef.current?.abort();
      const controller = new AbortController();
      quoteAbortRef.current = controller;
      setQuoteLoading(true);
      const res = await quoteSellerProduct(
        { price: priceValue, cost: costValue !== null && !Number.isNaN(costValue) ? costValue : null, product_id: productId },
        controller.signal
      );
      if (!mountedRef.current || controller.signal.aborted) return;
      setQuoteLoading(false);
      // คำนวณไม่ได้ = ซ่อนกล่องรายได้เงียบๆ (ไม่ขวางการบันทึก)
      setQuote(res.success ? res.data : null);
    }, QUOTE_DELAY_MS);
    return () => clearTimeout(timer);
  }, [meta, priceValue, costValue, productId]);

  // ---------- รูป ----------
  const maxTotal = 1 + (meta?.limits.max_gallery_images ?? 10);

  const stripImages: StripImage[] = isCreate
    ? localImages.map((uri, i) => ({ key: uri, uri, isMain: i === 0 }))
    : (product?.images ?? []).map((img) => ({ key: String(img.id), uri: img.url, isMain: img.is_main }));

  /** อัปเดตเฉพาะรูปจากผลของ server (ไม่ทับฟอร์มที่กำลังแก้) */
  const applyImages = (fresh: SellerProductDetail) =>
    setProduct((prev) => (prev ? { ...prev, images: fresh.images, main_image: fresh.main_image, image_count: fresh.image_count } : fresh));

  const addImages = async () => {
    if (imageBusy || readOnly) return;
    const remaining = maxTotal - stripImages.length;
    if (remaining <= 0) {
      Alert.alert('รูปครบแล้ว', `สินค้ามีรูปได้สูงสุด ${maxTotal} รูป ลบรูปเก่าก่อนนะ`);
      return;
    }
    const source = await choosePhotoSource('เพิ่มรูปสินค้า');
    if (!source || !mountedRef.current) return;
    const picked = await pickImages({ source, max: remaining, maxBytes: MAX_IMAGE_BYTES, aspect: [1, 1] });
    if (!picked || picked.uris.length === 0 || !mountedRef.current) return;

    if (isCreate) {
      setLocalImages((prev) => [...prev, ...picked.uris.filter((u) => !prev.includes(u))].slice(0, maxTotal));
      setErrors((prev) => ({ ...prev, images: undefined }));
      return;
    }
    if (!product) return;

    const controller = new AbortController();
    uploadAbortRef.current = controller;
    setImageBusy(true);
    const res = await addSellerProductImages(product.id, picked.uris, controller.signal);
    uploadAbortRef.current = null;
    if (!mountedRef.current || controller.signal.aborted) return;
    setImageBusy(false);
    if (res.success) {
      resultHaptic('success');
      applyImages(res.data);
      flash('อัปโหลดรูปแล้ว');
    } else {
      resultHaptic('error');
      Alert.alert('อัปโหลดรูปไม่สำเร็จ', res.message);
    }
  };

  /** ทำงานกับรูปบน server (ตั้งหลัก/เรียง/ลบ) แล้วอัปเดตแถบรูป */
  const runImageAction = async (action: () => Promise<{ success: boolean; data?: any; message?: string }>, done: string) => {
    if (imageBusy || !product) return;
    setImageBusy(true);
    const res = await action();
    if (!mountedRef.current) return;
    setImageBusy(false);
    if (res.success && res.data) {
      resultHaptic('success');
      applyImages(res.data as SellerProductDetail);
      flash(done);
    } else {
      resultHaptic('error');
      Alert.alert('ทำรายการไม่สำเร็จ', res.message || 'ลองใหม่อีกครั้งนะ');
      // ข้อมูลรูปอาจเปลี่ยนจากที่อื่น → ดึงใหม่
      if (productId) {
        const fresh = await getSellerProduct(productId);
        if (mountedRef.current && fresh.success) applyImages(fresh.data);
      }
    }
  };

  const onSetMain = (image: StripImage) => {
    if (isCreate) {
      setLocalImages((prev) => [image.uri, ...prev.filter((u) => u !== image.uri)]);
      return;
    }
    if (!product) return;
    runImageAction(() => setSellerProductMainImage(product.id, Number(image.key)), 'ตั้งรูปหลักแล้ว');
  };

  const onMove = (image: StripImage, direction: -1 | 1) => {
    if (isCreate) {
      setLocalImages((prev) => {
        const i = prev.indexOf(image.uri);
        const j = i + direction;
        // ช่อง 0 = รูปหลัก (เลื่อนแทนที่ไม่ได้)
        if (i < 1 || j < 1 || j >= prev.length) return prev;
        const next = [...prev];
        [next[i], next[j]] = [next[j], next[i]];
        return next;
      });
      return;
    }
    if (!product) return;
    const gallery = product.images.filter((img) => !img.is_main).map((img) => img.id);
    const i = gallery.indexOf(Number(image.key));
    const j = i + direction;
    if (i < 0 || j < 0 || j >= gallery.length) return;
    [gallery[i], gallery[j]] = [gallery[j], gallery[i]];
    runImageAction(() => reorderSellerProductImages(product.id, gallery), 'เรียงรูปใหม่แล้ว');
  };

  const onRemove = (image: StripImage) => {
    if (isCreate) {
      setLocalImages((prev) => prev.filter((u) => u !== image.uri));
      return;
    }
    if (!product) return;
    runImageAction(() => deleteSellerProductImage(product.id, image.isMain ? 0 : Number(image.key)), 'ลบรูปแล้ว');
  };

  // ---------- บันทึก ----------
  const applyServerErrors = (res: ApiFailure) => {
    if (!res.errors) return;
    const fieldErrors: Errors = {};
    Object.entries(res.errors).forEach(([key, list]) => {
      const msg = Array.isArray(list) ? list[0] : null;
      if (typeof msg !== 'string' || !THAI.test(msg)) return;
      const field = key.startsWith('images') || key === 'main_image' ? 'images' : key;
      (fieldErrors as Record<string, string>)[field] = msg;
    });
    setErrors(fieldErrors);
    // ช่องที่ผิดอยู่ในส่วนพับ → กางให้เห็น
    if (fieldErrors.brand || fieldErrors.weight || fieldErrors.dimensions || fieldErrors.sku) setShowMore(true);
  };

  const save = async () => {
    if (savingRef.current || readOnly) return;
    const found = validate(form, meta, stripImages.length, isCreate);
    if (Object.keys(found).length > 0) {
      setErrors(found);
      setSaveError('ตรวจข้อมูลที่ขึ้นสีแดงอีกครั้งนะ');
      if (found.brand || found.weight || found.dimensions || found.sku) setShowMore(true);
      resultHaptic('error');
      return;
    }

    const input = toInput(form);
    savingRef.current = true;
    setSaving(true);
    setSaveError(null);

    if (isCreate) {
      const controller = new AbortController();
      uploadAbortRef.current = controller;
      const res = await createSellerProduct(
        input,
        { mainUri: localImages[0] ?? null, galleryUris: localImages.slice(1) },
        idempotencyKeyRef.current,
        controller.signal
      );
      uploadAbortRef.current = null;
      savingRef.current = false;
      if (!mountedRef.current || controller.signal.aborted) return;
      setSaving(false);
      if (res.success) {
        resultHaptic('success');
        Alert.alert('เพิ่มสินค้าแล้ว', 'สินค้าเปิดขายทันที ลูกค้าเห็นในหน้าร้านแล้ว');
        setExiting(true);
        return;
      }
      resultHaptic('error');
      applyServerErrors(res);
      setSaveError(res.message);
      return;
    }

    if (!product) {
      savingRef.current = false;
      setSaving(false);
      return;
    }
    const res = await updateSellerProduct(product.id, input);
    savingRef.current = false;
    if (!mountedRef.current) return;
    setSaving(false);
    if (res.success) {
      resultHaptic('success');
      setProduct(res.data);
      resetForm(res.data);
      flash('บันทึกสินค้าแล้ว');
      return;
    }
    resultHaptic('error');
    if (res.status === 423 || res.status === 404) {
      // สินค้าถูกระงับ/ลบระหว่างแก้ → โหลดใหม่ให้เห็นสถานะจริง
      Alert.alert('บันทึกไม่ได้', res.message);
      load('refresh');
      return;
    }
    applyServerErrors(res);
    setSaveError(res.message);
  };

  // ---------- เปิด/ปิดขาย (ทันที) ----------
  const toggleActive = async (next: boolean) => {
    if (!product || activeBusy || product.is_blocked) return;
    setActiveBusy(true);
    setProduct({ ...product, is_active: next });
    const res = await setSellerProductActive(product.id, next);
    if (!mountedRef.current) return;
    setActiveBusy(false);
    if (res.success) {
      resultHaptic('success');
      setProduct((prev) => (prev ? { ...prev, is_active: res.data.is_active } : res.data));
      flash(next ? 'เปิดขายแล้ว' : 'ปิดการขายแล้ว ลูกค้าจะไม่เห็นสินค้านี้');
    } else {
      setProduct((prev) => (prev ? { ...prev, is_active: !next } : prev));
      resultHaptic('error');
      Alert.alert('เปลี่ยนไม่สำเร็จ', res.message);
    }
  };

  // ---------- ลบสินค้า (ถามก่อน) ----------
  const confirmDelete = () => {
    if (!product || readOnly) return;
    Alert.alert('ลบสินค้านี้?', `"${product.name}" จะหายจากหน้าร้านทันที ออเดอร์เก่ายังดูได้ตามเดิม`, [
      { text: 'ไม่ลบ', style: 'cancel' },
      {
        text: 'ลบสินค้า',
        style: 'destructive',
        onPress: async () => {
          if (savingRef.current) return;
          savingRef.current = true;
          setSaving(true);
          const res = await deleteSellerProduct(product.id);
          savingRef.current = false;
          if (!mountedRef.current) return;
          setSaving(false);
          if (res.success) {
            resultHaptic('success');
            setExiting(true);
          } else {
            resultHaptic('error');
            Alert.alert('ลบไม่สำเร็จ', res.message);
          }
        },
      },
    ]);
  };

  // ---------- หมวดหมู่ ----------
  const categories = meta?.categories ?? [];
  const categoryName = (id: number | null): string | null => {
    if (!id) return null;
    const cat = categories.find((c) => c.id === id);
    if (!cat) return product?.category?.id === id ? product.category.name : null;
    const parent = cat.parent_id ? categories.find((c) => c.id === cat.parent_id) : null;
    return parent ? `${parent.name} › ${cat.name}` : cat.name;
  };
  const filteredCategories = useMemo(() => {
    const q = categorySearch.trim().toLowerCase();
    return q ? categories.filter((c) => c.name.toLowerCase().includes(q)) : categories;
  }, [categories, categorySearch]);

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title={title} scroll={false}>
        <EmptyState art="bag" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (!isCreate && !productId) {
    return (
      <Screen title={title} scroll={false}>
        <EmptyState variant="error" title="ไม่พบสินค้านี้" message="ลิงก์สินค้าไม่ถูกต้อง" actionLabel="กลับ" onAction={() => router.back()} />
      </Screen>
    );
  }

  if (loading && !meta) {
    return (
      <Screen title={title}>
        <FormSkeleton sections={4} />
      </Screen>
    );
  }

  if (loadError && (!meta || (!isCreate && !product))) {
    return (
      <Screen title={title} onRefresh={() => load('refresh')} refreshing={refreshing}>
        {isGateFailure(loadError) ? (
          <SellerGateNotice failure={loadError} />
        ) : (
          <EmptyState
            variant="error"
            title={loadError.status === 404 ? 'ไม่พบสินค้านี้' : 'เปิดสินค้านี้ไม่ได้'}
            message={loadError.message}
            actionLabel="ลองใหม่"
            onAction={() => load('initial')}
            secondaryActionLabel="กลับไปรายการสินค้า"
            onSecondaryAction={() => router.back()}
          />
        )}
      </Screen>
    );
  }

  const shippingMethods = meta?.shipping_methods.length ? meta.shipping_methods : FALLBACK_SHIPPING;
  const compareValue = parseMoney(form.compare_at_price);
  const compareWarn =
    compareValue !== null && !Number.isNaN(compareValue) && priceValue !== null && !Number.isNaN(priceValue) && compareValue > 0 && compareValue <= priceValue;
  const gp = meta?.gp;

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title={title}
        subtitle={product?.name}
        refreshing={refreshing}
        onRefresh={isCreate || dirty ? undefined : () => load('refresh')}
        right={dirty ? <Pill label="ยังไม่บันทึก" tone="warning" /> : undefined}
      >
        {!!notice && <NoticeBanner tone="success" text={notice} style={styles.block} />}

        {/* ---------- อ่านอย่างเดียว ---------- */}
        {readOnly && product && (
          <Card3D padding={spacing.lg} style={styles.block}>
            <View style={styles.row}>
              <IconTile icon={product.is_blocked ? 'prohibit' : 'list'} tone={product.is_blocked ? 'danger' : 'info'} size={44} />
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  {product.is_blocked ? 'สินค้าถูกระงับโดยทีมงาน' : 'แก้สินค้านี้บนเว็บไซต์'}
                </Text>
                <Text style={[typography.bodySm, styles.gapXs, { color: product.is_blocked ? colors.danger : colors.textMuted }]}>
                  {product.read_only_reason}
                </Text>
              </View>
            </View>
            {product.is_blocked ? (
              <Button3D title="ติดต่อทีมงาน" icon="headset" variant="secondary" size="sm" style={styles.gapMd} onPress={() => router.push('/support')} />
            ) : (
              <WebsiteButton path={product.web_edit_path} label="แก้ไขบนเว็บไซต์" icon="globe" size="sm" style={styles.gapMd} />
            )}
          </Card3D>
        )}

        {/* ---------- รูป ---------- */}
        <View style={styles.sectionHead}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>รูปสินค้า</Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            {readOnly ? `${stripImages.length} รูป` : `แตะรูปเพื่อตั้งรูปหลัก เลื่อน หรือลบ · สูงสุด ${maxTotal} รูป ไม่เกิน 5MB`}
          </Text>
        </View>
        <ProductImageStrip
          images={stripImages}
          maxTotal={maxTotal}
          busy={imageBusy}
          readOnly={readOnly}
          onAdd={addImages}
          onSetMain={onSetMain}
          onMove={onMove}
          onRemove={onRemove}
        />
        {!!errors.images && <Text style={[typography.caption, styles.gapXs, { color: colors.danger }]}>{errors.images}</Text>}

        {/* ---------- ข้อมูลสินค้า ---------- */}
        <FormCard icon="tag" title="ข้อมูลสินค้า" style={styles.sectionGap}>
          <Field
            label="ชื่อสินค้า"
            required
            value={form.name}
            onChangeText={(v) => setText('name', v)}
            error={errors.name}
            placeholder="เช่น เสื้อยืดผ้าฝ้ายลายไทย"
            maxLength={255}
            editable={!readOnly}
          />
          <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>
            หมวดหมู่ <Text style={{ color: colors.danger }}>*</Text>
          </Text>
          <Pressable
            onPress={() => {
              if (readOnly) return;
              setCategorySearch('');
              setCategorySheet(true);
            }}
            disabled={readOnly}
            accessibilityRole="button"
            accessibilityLabel={`หมวดหมู่ ${categoryName(form.category_id) ?? 'ยังไม่ได้เลือก'}`}
            style={[
              styles.select,
              { backgroundColor: colors.inset, borderColor: errors.category_id ? colors.danger : colors.border },
            ]}
          >
            <Icon name="squares-four" size={18} color={colors.textMuted} />
            <Text numberOfLines={1} style={[typography.body, styles.flex, { color: form.category_id ? colors.textStrong : colors.textFaint }]}>
              {categoryName(form.category_id) ?? 'เลือกหมวดหมู่'}
            </Text>
            {!readOnly && <Icon name="caret-down" size={16} color={colors.textMuted} />}
          </Pressable>
          {!!errors.category_id && <Text style={[typography.caption, styles.gapXs, { color: colors.danger }]}>{errors.category_id}</Text>}
          <Field
            label="คำอธิบายสั้น"
            value={form.short_description}
            onChangeText={(v) => setText('short_description', v)}
            error={errors.short_description}
            placeholder="สรุปจุดเด่นของสินค้าในประโยคเดียว"
            multiline
            maxLength={500}
            editable={!readOnly}
          />
          <Field
            label="รายละเอียดสินค้า"
            value={form.description}
            onChangeText={(v) => setText('description', v)}
            error={errors.description}
            placeholder="วัสดุ ขนาด วิธีใช้ การรับประกัน"
            multiline
            maxLength={20000}
            editable={!readOnly}
          />
        </FormCard>

        {/* ---------- ราคา + รายได้ ---------- */}
        <FormCard icon="coins" title="ราคา" tone="gold">
          <View style={styles.fieldRow}>
            <Field
              label="ราคาขาย (บาท)"
              required
              value={form.price}
              onChangeText={setMoneyText('price')}
              error={errors.price}
              keyboardType="decimal-pad"
              placeholder="0"
              containerStyle={styles.flex}
              editable={!readOnly}
            />
            <Field
              label="ราคาก่อนลด"
              value={form.compare_at_price}
              onChangeText={setMoneyText('compare_at_price')}
              error={errors.compare_at_price}
              keyboardType="decimal-pad"
              placeholder="ไม่บังคับ"
              containerStyle={styles.flex}
              editable={!readOnly}
            />
          </View>
          {compareWarn && (
            <Text style={[typography.caption, styles.gapXs, { color: colors.warning }]}>
              ราคาก่อนลดควรสูงกว่าราคาขาย ไม่อย่างนั้นลูกค้าจะไม่เห็นป้ายลดราคา
            </Text>
          )}
          <Field
            label="ต้นทุนต่อชิ้น (ใช้คำนวณกำไร ลูกค้าไม่เห็น)"
            value={form.cost_price}
            onChangeText={setMoneyText('cost_price')}
            error={errors.cost_price}
            keyboardType="decimal-pad"
            placeholder="ไม่บังคับ"
            editable={!readOnly}
          />

          {/* GP / VAT (อ่านอย่างเดียว) */}
          <View style={[styles.gpRow, { backgroundColor: colors.inset }]}>
            <Icon name="lock" size={15} color={colors.textMuted} />
            <View style={styles.flex}>
              <Text style={[typography.caption, { color: colors.textStrong }]}>
                ค่า GP แพลตฟอร์ม {gp?.rate !== null && gp?.rate !== undefined ? `${gp.rate.toLocaleString('th-TH')}%` : ''}
                {gp?.promo_active ? ' · โปรฯ GP ฟรีช่วงเปิดตัว' : ''}
              </Text>
              {!!gp?.label && <Text style={[typography.micro, { color: colors.textMuted }]}>{gp.label}</Text>}
              <Text style={[typography.micro, { color: colors.textMuted }]}>
                {meta?.vat_registered ? 'ร้านจดทะเบียน VAT — ระบบถอด VAT 7/107 ออกจากยอดขายก่อนโอน' : 'ร้านไม่ได้จด VAT'}
              </Text>
            </View>
          </View>

          {/* รายได้ต่อชิ้น */}
          {(quote || quoteLoading) && (
            <View style={[styles.quoteBox, { borderColor: colors.gold, backgroundColor: colors.goldSoft }]}>
              <View style={styles.row}>
                <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>ร้านได้รับต่อชิ้น</Text>
                {quote ? (
                  <PriceText amount={quote.seller_net} size="lg" tone="gold" style={quoteLoading ? styles.dim : undefined} />
                ) : (
                  <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังคำนวณ...</Text>
                )}
              </View>
              {quote?.lines
                .filter((l) => l.key !== 'seller_net')
                .map((line) => (
                  <View key={line.key} style={styles.quoteLine}>
                    <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>{line.label}</Text>
                    <PriceText amount={line.amount} size="xs" tone={line.amount < 0 ? 'muted' : 'default'} signed />
                  </View>
                ))}
              {quote?.is_loss && (
                <Text style={[typography.caption, styles.gapXs, { color: colors.danger }]}>ราคานี้ขาดทุนเมื่อเทียบกับต้นทุน</Text>
              )}
              {quote?.warnings.map((w) => (
                <Text key={w} style={[typography.caption, styles.gapXs, { color: colors.warning }]}>
                  {w}
                </Text>
              ))}
            </View>
          )}
        </FormCard>

        {/* ---------- สต็อก ---------- */}
        <FormCard icon="package" title="สต็อกสินค้า">
          <Field
            label="จำนวนในสต็อก"
            required
            value={form.stock_quantity}
            onChangeText={(v) => setText('stock_quantity', v.replace(/[^0-9]/g, '').slice(0, 7))}
            error={errors.stock_quantity}
            keyboardType="number-pad"
            editable={!readOnly}
            hint="ใส่ 0 = สินค้าหมด ลูกค้าจะสั่งไม่ได้จนกว่าจะเติม"
          />
          <ToggleRow
            icon="chart-bar"
            title="ติดตามสต็อก"
            description="ระบบตัดสต็อกเมื่อขาย และหยุดรับออเดอร์เมื่อสินค้าหมด"
            value={form.track_inventory}
            disabled={readOnly}
            onValueChange={(v) => setForm((prev) => ({ ...prev, track_inventory: v }))}
          />
        </FormCard>

        {/* ---------- การจัดส่ง ---------- */}
        <FormCard icon="truck" title="การจัดส่ง" subtitle="ส่งด้วยไรเดอร์ตั้งที่หน้าตั้งค่าร้าน">
          <View style={styles.chipRow}>
            {shippingMethods.map((m) => (
              <Chip
                key={m.value}
                label={m.label}
                size="sm"
                selected={form.shipping_method === m.value}
                disabled={readOnly}
                onPress={() => {
                  setForm((prev) => ({ ...prev, shipping_method: m.value }));
                  setErrors((prev) => ({ ...prev, shipping_fee: undefined, shipping_weight_kg: undefined, free_shipping_min_amount: undefined }));
                }}
              />
            ))}
          </View>
          <Text style={[typography.caption, styles.gapSm, { color: colors.textMuted }]}>{SHIPPING_HINT[form.shipping_method]}</Text>
          {form.shipping_method === 'flat_rate' && (
            <Field
              label={`ค่าส่งต่อชิ้น (บาท · สูงสุด ${(meta?.limits.max_shipping_fee ?? 5000).toLocaleString('th-TH')})`}
              required
              value={form.shipping_fee}
              onChangeText={setMoneyText('shipping_fee')}
              error={errors.shipping_fee}
              keyboardType="decimal-pad"
              editable={!readOnly}
            />
          )}
          {form.shipping_method === 'weight_based' && (
            <Field
              label="น้ำหนักจัดส่ง (กก. รวมบรรจุภัณฑ์)"
              required
              value={form.shipping_weight_kg}
              onChangeText={(v) => setText('shipping_weight_kg', v.replace(/[^0-9.]/g, '').slice(0, 9))}
              error={errors.shipping_weight_kg}
              keyboardType="decimal-pad"
              placeholder="เช่น 0.5"
              editable={!readOnly}
            />
          )}
          {(form.shipping_method === 'flat_rate' || form.shipping_method === 'weight_based') && (
            <Field
              label="ส่งฟรีเมื่อซื้อครบ (บาท)"
              value={form.free_shipping_min_amount}
              onChangeText={setMoneyText('free_shipping_min_amount')}
              error={errors.free_shipping_min_amount}
              keyboardType="decimal-pad"
              placeholder="ว่าง = ไม่มี"
              editable={!readOnly}
            />
          )}
        </FormCard>

        {/* ---------- รายละเอียดเพิ่มเติม (พับได้) ---------- */}
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <Pressable
            onPress={() => setShowMore((v) => !v)}
            accessibilityRole="button"
            accessibilityState={{ expanded: showMore }}
            accessibilityLabel="รายละเอียดเพิ่มเติม"
            style={styles.row}
          >
            <IconTile icon="sliders-horizontal" size={38} />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>รายละเอียดเพิ่มเติม</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>แบรนด์ น้ำหนัก ขนาด รหัสสินค้า (SKU)</Text>
            </View>
            <Icon name={showMore ? 'caret-up' : 'caret-down'} size={18} color={colors.textMuted} />
          </Pressable>
          {showMore && (
            <>
              <Field label="แบรนด์" value={form.brand} onChangeText={(v) => setText('brand', v)} error={errors.brand} placeholder="ชื่อแบรนด์หรือผู้ผลิต" maxLength={100} editable={!readOnly} />
              <View style={styles.fieldRow}>
                <Field
                  label="น้ำหนัก (กรัม)"
                  value={form.weight}
                  onChangeText={setMoneyText('weight')}
                  error={errors.weight}
                  keyboardType="decimal-pad"
                  containerStyle={styles.flex}
                  editable={!readOnly}
                />
                <Field
                  label="ขนาด"
                  value={form.dimensions}
                  onChangeText={(v) => setText('dimensions', v)}
                  error={errors.dimensions}
                  placeholder="กxยxส ซม."
                  maxLength={100}
                  containerStyle={styles.flex}
                  editable={!readOnly}
                />
              </View>
              <Field
                label="รหัสสินค้า (SKU)"
                value={form.sku}
                onChangeText={(v) => setText('sku', v.replace(/\s/g, ''))}
                error={errors.sku}
                placeholder="ว่าง = สร้างให้อัตโนมัติ"
                autoCapitalize="characters"
                autoCorrect={false}
                maxLength={100}
                editable={!readOnly}
              />
            </>
          )}
        </Card3D>

        {/* ---------- สถานะการขาย / ลบ ---------- */}
        {isCreate ? (
          <View style={[styles.infoRow, { backgroundColor: colors.infoSoft }]}>
            <Icon name="info" size={16} color={colors.info} />
            <Text style={[typography.caption, styles.flex, { color: colors.text }]}>สินค้าจะเปิดขายทันทีหลังบันทึก ปิดการขายได้ภายหลัง</Text>
          </View>
        ) : product ? (
          <FormCard icon={product.is_active ? 'eye' : 'eye-slash'} title="สถานะการขาย" tone={product.is_active ? 'success' : 'neutral'}>
            <ToggleRow
              icon="storefront"
              title="เปิดขาย"
              description={product.is_blocked ? 'ถูกระงับโดยทีมงาน' : product.is_active ? 'ลูกค้าเห็นและสั่งซื้อได้' : 'ซ่อนจากลูกค้าชั่วคราว'}
              value={product.is_active}
              disabled={product.is_blocked || activeBusy}
              onValueChange={toggleActive}
            />
            <View style={styles.statsRow}>
              <Pill label={`ขายแล้ว ${product.sales_count.toLocaleString('th-TH')}`} icon="receipt" />
              <Pill label={`เข้าชม ${product.view_count.toLocaleString('th-TH')}`} icon="eye" />
              {product.is_public_approved && <Pill label="อยู่ในตลาดสมาชิก" tone="success" icon="seal-check" />}
              {product.is_hidden && <Pill label="ทีมงานซ่อนไว้" tone="warning" icon="eye-slash" />}
            </View>
            {!readOnly && (
              <Button3D title="ลบสินค้านี้" icon="trash" variant="danger" size="sm" style={styles.deleteBtn} onPress={confirmDelete} disabled={saving} />
            )}
          </FormCard>
        ) : null}

        {!!saveError && <ErrorNote text={saveError} />}
        <View style={styles.bottomSpace} />
      </Screen>

      {!readOnly && (
        <StickyBar>
          <Button3D
            title={isCreate ? 'บันทึกและเปิดขาย' : dirty ? 'บันทึกการแก้ไข' : 'บันทึกแล้ว'}
            icon={isCreate || dirty ? 'check-circle' : 'seal-check'}
            size="lg"
            fullWidth
            variant={isCreate || dirty ? 'primary' : 'secondary'}
            disabled={!isCreate && !dirty}
            loading={saving}
            loadingText={isCreate && localImages.length > 0 ? 'กำลังอัปโหลดรูปและบันทึก...' : 'กำลังบันทึก...'}
            onPress={save}
          />
        </StickyBar>
      )}

      {/* เลือกหมวดหมู่ */}
      <FormSheet visible={categorySheet} icon="squares-four" title="เลือกหมวดหมู่" cancelLabel="ปิด" onClose={() => setCategorySheet(false)}>
        <SearchField value={categorySearch} onChangeText={setCategorySearch} placeholder="ค้นหาหมวดหมู่" style={styles.gapSm} />
        <View style={styles.catList}>
          {filteredCategories.length === 0 ? (
            <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>ไม่พบหมวดหมู่ที่ค้นหา</Text>
          ) : (
            filteredCategories.map((c) => {
              const selected = form.category_id === c.id;
              return (
                <Pressable
                  key={c.id}
                  onPress={() => {
                    setForm((prev) => ({ ...prev, category_id: c.id }));
                    setErrors((prev) => ({ ...prev, category_id: undefined }));
                    setCategorySheet(false);
                  }}
                  accessibilityRole="radio"
                  accessibilityState={{ selected }}
                  style={({ pressed }) => [
                    styles.catRow,
                    { borderBottomColor: colors.divider, backgroundColor: selected ? colors.goldSoft : 'transparent', opacity: pressed ? 0.7 : 1 },
                  ]}
                >
                  <Text numberOfLines={1} style={[typography.body, styles.flex, { color: colors.textStrong }]}>
                    {c.parent_id ? `   ${categoryName(c.id)}` : c.name}
                  </Text>
                  {selected && <Icon name="check" size={18} color={colors.goldDeep} weight="bold" />}
                </Pressable>
              );
            })
          )}
        </View>
      </FormSheet>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  block: {
    marginBottom: spacing.lg,
  },
  sectionGap: {
    marginTop: spacing.xl,
  },
  gapXs: {
    marginTop: spacing.xs,
  },
  gapSm: {
    marginTop: spacing.sm,
  },
  gapMd: {
    marginTop: spacing.md,
    alignSelf: 'flex-start',
  },
  dim: {
    opacity: 0.5,
  },
  sectionHead: {
    marginBottom: spacing.sm,
    gap: 2,
  },
  label: {
    marginTop: spacing.md,
    marginBottom: 6,
  },
  select: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    minHeight: 48,
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
  },
  fieldRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  gpRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: radii.sm,
    padding: spacing.md,
  },
  quoteBox: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
    gap: 6,
  },
  quoteLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  statsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  deleteBtn: {
    marginTop: spacing.lg,
    alignSelf: 'flex-start',
  },
  bottomSpace: {
    height: 90,
  },
  catList: {
    marginTop: spacing.sm,
  },
  catRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    minHeight: 48,
    paddingHorizontal: spacing.sm,
    borderBottomWidth: 1,
    borderRadius: 10,
  },
});
