<?php

namespace App\OpenApi;

/**
 * Ponto único para as definições globais do OpenAPI (info, servers,
 * segurança e schemas compartilhados entre múltiplos endpoints). Schemas
 * específicos de um recurso ficam junto ao Resource que os representa.
 *
 * @OA\Info(
 *     version="1.0.0",
 *     title="Product API",
 *     description="API RESTful para gerenciamento de produtos, com autenticação JWT, filtros avançados e auditoria assíncrona."
 * )
 *
 * @OA\Server(url="/", description="Servidor atual")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *
 *     @OA\Property(property="message", type="string", example="Recurso não encontrado.")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *
 *     @OA\Property(property="message", type="string", example="Os dados informados são inválidos."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         example={"nome"={"O nome do produto é obrigatório."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *     @OA\Property(property="token_type", type="string", example="bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=3600, description="Tempo de vida do token em segundos")
 * )
 */
class OpenApiSpec
{
    //
}
