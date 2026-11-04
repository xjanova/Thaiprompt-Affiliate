<?php

namespace App\Services\Stt;

/**
 * 🎙️ (2026-11-04) ตัวถอดเสียงพูดเป็นข้อความ (ไมค์ในห้องแชทเว็บจันทรา)
 *
 * ลำดับที่เจ้าของเลือก: เบราว์เซอร์ถอดเอง (Web Speech ฟรี) ก่อน → ใช้ไม่ได้ค่อยส่งมาที่นี่
 * ❌ ไม่ใช้ Gemini กับเสียงลูกค้า (รุ่นฟรีเอาข้อมูลไปใช้ · รุ่นจ่ายแพงกว่า Scribe ~2.5 เท่า)
 *
 * Contract:
 *   transcribe($path) → ['success', 'text', 'words' => [{text,start,end}], 'language', 'duration', 'error']
 *   words = เวลาของแต่ละคำ (วินาที) — เว็บใช้ตัดคำเติมของแม่หมอที่หลุดเข้าไมค์ออก
 */
interface SttProviderInterface
{
    public function name(): string;

    public function isAvailable(): bool;

    /**
     * @param  string  $path  ไฟล์เสียงในเครื่อง (webm/ogg/mp4/mp3/wav)
     * @param  array{language?:string,mime?:string}  $options
     * @return array{success:bool,text:string,words:array<int,array{text:string,start:float,end:float}>,language:?string,duration:float,error:?string}
     */
    public function transcribe(string $path, array $options = []): array;
}
