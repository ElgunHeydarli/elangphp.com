# Süni intellektlə işləmək

Kodlaşdırma köməkçilərinə Elang haqqında aktual və dəqiq kontekst vermək üçün versiyalandırılmış sənədləşmədən istifadə edin. Elang ayrıca dil deyil, PHP freymvorkudur.

## Sənədləşmənin giriş nöqtəsi

Qısa indeks üçün `/llms.txt` oxuyun. Hər versiyalandırılmış sənədləşmə səhifəsinin eyni URL-in sonuna `.md` əlavə edilmiş Markdown təsviri var. HTML head hissəsi bu alternativ təsvirləri elan edir.

## Layihə qaydaları

Repozitoriyaya arxitektura, əmrlər və reallaşdırma sərhədləri göstərilən AGENTS.md daxildir. Dəyişiklik istəyərkən kodlaşdırma alətlərinə uyğun mənbə fayllarını və sənədləşməni verin.

## Faydalı sorğu

```text
Build an Elang PHP controller for a JSON endpoint.
Read the v0.1 routing, validation, and controllers documentation first.
Use Symfony HttpFoundation requests, Elang Response::json, and
Symfony Constraint objects. Do not assume Laravel facades or an ORM.
Write a test for the invalid-input response.
```

## Məhdudiyyətlər

Sənədləşmənin yayımlanması modeli avtomatik öyrətmir və ya süni intellekt axtarışında istinadlara zəmanət vermir. Yalnız oxumağa imkan verən MCP sənədləşmə serveri /mcp ünvanında və php elang docs:serve vasitəsilə əlçatandır. Semantik axtarış və xüsusi redaktor əlavələri gələcək işlərdir. Cari axtarış API-si lokal sənədləşmə üzərində məhdud mətn axtarışı aparır.

## Sənədləşmə serverinə qoşulun

İctimai, yalnız oxumağa imkan verən Streamable HTTP son nöqtəsi `https://elangphp.com/mcp` ünvanıdır. O, search_docs və read_doc alətlərini, həmçinin siyahıya alınmış elang:// Markdown resurslarını təqdim edir. İctimai sənədləşmə üçün hesab tokeni lazım deyil. O, fərqli mənbəli brauzer sorğularını rədd edir və yazma və ya icra alətləri təklif etmir.

Lokal stdio müştərisi üçün sənədləşmə veb-saytının işçi nüsxəsindən `php elang docs:serve` yerinə yetirin. Standart çıxışda yalnız JSON-RPC mesajları olur. Bu reallaşdırma MCP 2025-11-25 protokolunu dəstəkləyir.
